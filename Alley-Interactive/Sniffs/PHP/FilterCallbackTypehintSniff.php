<?php
/**
 * FilterCallbackTypehintSniff class file
 *
 * @package Alley\CodingStandards
 */

namespace Alley\Sniffs\PHP;

use PHP_CodeSniffer\Util\Tokens;
use WordPressCS\WordPress\Sniff;

/**
 * Flags typehints on filter callback functions.
 *
 * Typehints on WordPress filter callbacks are dangerous: the types passed
 * by core or other plugins may not match the declared types, resulting in fatal
 * errors. Type checking should be done inside the callback body instead.
 *
 * Handles closures, arrow functions, static closures, string callbacks,
 * short/long array callbacks, and PHP 8.1 first-class callable syntax.
 *
 * Error codes:
 *  - Alley.PHP.FilterCallbackTypehint.ParameterTypehint
 */
class FilterCallbackTypehintSniff extends Sniff {

	/**
	 * Token positions already checked in the current file, to avoid duplicate
	 * errors when the same function is registered as a callback more than once.
	 *
	 * @var array<int, bool>
	 */
	private array $checked = [];

	/**
	 * Path of the file currently being processed.
	 *
	 * @var string
	 */
	private string $current_file = '';

	/**
	 * Returns tokens to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return [ T_STRING ];
	}

	/**
	 * Processes this sniff when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 */
	public function process_token( $stackPtr ) {
		$tokens = $this->phpcsFile->getTokens();

		// Only process add_filter() calls.
		if ( 'add_filter' !== $tokens[ $stackPtr ]['content'] ) {
			return;
		}

		// Skip method/property access (e.g. $obj->add_filter() or Cls::add_filter()).
		$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 1, null, true );
		if ( false !== $prev && in_array( $tokens[ $prev ]['code'], [ T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON ], true ) ) {
			return;
		}

		// Must be a function call: next non-empty token must be '('.
		$open_paren = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true );
		if ( false === $open_paren || T_OPEN_PARENTHESIS !== $tokens[ $open_paren ]['code'] ) {
			return;
		}

		// Reset per-file deduplication state when processing a new file.
		$file_path = $this->phpcsFile->getFilename();
		if ( $this->current_file !== $file_path ) {
			$this->current_file = $file_path;
			$this->checked      = [];
		}

		// Find the callback — the second argument.
		$callback_ptr = $this->get_second_argument_start( $open_paren );
		if ( false === $callback_ptr ) {
			return;
		}

		// Resolve the callback expression to the underlying function/closure token.
		$function_token = $this->resolve_function_token(
			$callback_ptr,
			$tokens[ $open_paren ]['parenthesis_closer']
		);
		if ( false === $function_token ) {
			return;
		}

		// Avoid duplicate errors when the same function is registered more than once.
		if ( isset( $this->checked[ $function_token ] ) ) {
			return;
		}
		$this->checked[ $function_token ] = true;

		$this->check_for_typehints( $function_token );
	}

	/**
	 * Returns the first token of the second argument in a function call.
	 *
	 * @param int $open_paren Position of the opening parenthesis.
	 * @return int|false
	 */
	private function get_second_argument_start( int $open_paren ) {
		$tokens      = $this->phpcsFile->getTokens();
		$close_paren = $tokens[ $open_paren ]['parenthesis_closer'];
		$depth       = 0;

		for ( $i = $open_paren + 1; $i < $close_paren; $i++ ) {
			$code = $tokens[ $i ]['code'];

			if ( in_array( $code, [ T_OPEN_PARENTHESIS, T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET ], true ) ) {
				++$depth;
			} elseif ( in_array( $code, [ T_CLOSE_PARENTHESIS, T_CLOSE_SHORT_ARRAY, T_CLOSE_SQUARE_BRACKET ], true ) ) {
				--$depth;
			} elseif ( T_COMMA === $code && 0 === $depth ) {
				return $this->phpcsFile->findNext( Tokens::$emptyTokens, $i + 1, null, true );
			}
		}

		return false;
	}

	/**
	 * Resolves a callback expression to the T_FUNCTION / T_CLOSURE / T_FN token.
	 *
	 * Supports:
	 *   - Inline closures:              function( $x ) {}
	 *   - Arrow functions:              fn( $x ) => $x
	 *   - Static closures/arrows:       static function( $x ) {}
	 *   - String callbacks:             'my_function' or 'ClassName::method'
	 *   - Short array callbacks:        [ $this, 'method' ]
	 *   - Long array callbacks:         array( $this, 'method' )
	 *   - First-class callables (8.1):  $this->method(...), Cls::method(...), fn(...)
	 *
	 * @param int $callback_start    First token of the callback expression.
	 * @param int $outer_close_paren Closing paren of the add_filter call.
	 * @return int|false
	 */
	private function resolve_function_token( int $callback_start, int $outer_close_paren ) {
		$tokens = $this->phpcsFile->getTokens();
		$code   = $tokens[ $callback_start ]['code'];

		// Inline closure: function( ... ) { ... }
		if ( T_CLOSURE === $code ) {
			return $callback_start;
		}

		// Arrow function: fn( ... ) => ...
		if ( T_FN === $code ) {
			return $callback_start;
		}

		// Static closure or static arrow function: static function( ... ) { ... }
		if ( T_STATIC === $code ) {
			$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $callback_start + 1, null, true );
			if ( false !== $next && in_array( $tokens[ $next ]['code'], [ T_CLOSURE, T_FN ], true ) ) {
				return $next;
			}
			// Could be static::method(...) — try first-class callable resolution.
			return $this->resolve_first_class_callable( $callback_start, $outer_close_paren );
		}

		// String literal: 'function_name' or 'ClassName::method_name'
		if ( T_CONSTANT_ENCAPSED_STRING === $code ) {
			$name = trim( $tokens[ $callback_start ]['content'], "'\"" );
			if ( false !== strpos( $name, '::' ) ) {
				[ , $method_name ] = explode( '::', $name, 2 );
				return $this->find_named_function_in_file( $method_name );
			}
			return $this->find_named_function_in_file( $name );
		}

		// Short array: [ $this, 'method' ] or [ ClassName::class, 'method' ]
		if ( T_OPEN_SHORT_ARRAY === $code ) {
			return $this->resolve_array_callback( $callback_start, false );
		}

		// Long array: array( $this, 'method' )
		if ( T_ARRAY === $code ) {
			$array_open = $this->phpcsFile->findNext( T_OPEN_PARENTHESIS, $callback_start + 1 );
			if ( false !== $array_open ) {
				return $this->resolve_array_callback( $array_open, true );
			}
			return false;
		}

		// First-class callable or other expression starting with a variable/name:
		// $this->method(...), $var(...), ClassName::method(...), my_function(...)
		if ( in_array( $code, [ T_VARIABLE, T_STRING, T_SELF, T_PARENT ], true ) ) {
			return $this->resolve_first_class_callable( $callback_start, $outer_close_paren );
		}

		return false;
	}

	/**
	 * Extracts the method name from an array-form callback and returns its function token.
	 *
	 * @param int  $open_bracket Position of [ or ( that opens the array.
	 * @param bool $is_paren     True when the opener is T_OPEN_PARENTHESIS (long array syntax).
	 * @return int|false
	 */
	private function resolve_array_callback( int $open_bracket, bool $is_paren ) {
		$tokens = $this->phpcsFile->getTokens();

		$close_bracket = $is_paren
			? $tokens[ $open_bracket ]['parenthesis_closer']
			: $tokens[ $open_bracket ]['bracket_closer'];

		for ( $i = $open_bracket + 1; $i < $close_bracket; $i++ ) {
			if ( T_COMMA !== $tokens[ $i ]['code'] ) {
				continue;
			}

			$method_name_ptr = $this->phpcsFile->findNext( Tokens::$emptyTokens, $i + 1, $close_bracket, true );
			if ( false === $method_name_ptr || T_CONSTANT_ENCAPSED_STRING !== $tokens[ $method_name_ptr ]['code'] ) {
				return false;
			}

			$method_name = trim( $tokens[ $method_name_ptr ]['content'], "'\"" );
			return $this->find_named_function_in_file( $method_name );
		}

		return false;
	}

	/**
	 * Resolves a first-class callable expression to the function token.
	 *
	 * Detects the PHP 8.1 `callable(...)` pattern by looking for a call whose sole
	 * argument is `T_ELLIPSIS`, then resolves the callee name to a definition in the
	 * current file.
	 *
	 * @param int $start_ptr        First token of the callable expression.
	 * @param int $outer_close_paren Outer closing paren of the add_filter call.
	 * @return int|false
	 */
	private function resolve_first_class_callable( int $start_ptr, int $outer_close_paren ) {
		$tokens = $this->phpcsFile->getTokens();

		// Find the opening paren of the call within the argument bounds.
		$open_paren = $this->phpcsFile->findNext( T_OPEN_PARENTHESIS, $start_ptr, $outer_close_paren );
		if ( false === $open_paren ) {
			return false;
		}

		$inner_close  = $tokens[ $open_paren ]['parenthesis_closer'];
		$first_inside = $this->phpcsFile->findNext( Tokens::$emptyTokens, $open_paren + 1, $inner_close, true );

		// The only content inside the parens must be `...` for this to be a first-class callable.
		if ( false === $first_inside || T_ELLIPSIS !== $tokens[ $first_inside ]['code'] ) {
			return false;
		}

		// The callee name is the T_STRING immediately before the opening paren.
		$name_ptr = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $open_paren - 1, null, true );
		if ( false === $name_ptr || T_STRING !== $tokens[ $name_ptr ]['code'] ) {
			return false;
		}

		return $this->find_named_function_in_file( $tokens[ $name_ptr ]['content'] );
	}

	/**
	 * Searches the current file for a function or method definition by name.
	 *
	 * @param string $name Function or method name.
	 * @return int|false Position of the T_FUNCTION token, or false if not found.
	 */
	private function find_named_function_in_file( string $name ) {
		$tokens      = $this->phpcsFile->getTokens();
		$token_count = $this->phpcsFile->numTokens;
		$ptr         = 0;

		while ( false !== ( $ptr = $this->phpcsFile->findNext( T_FUNCTION, $ptr + 1, $token_count ) ) ) {
			$name_ptr = $this->phpcsFile->findNext( Tokens::$emptyTokens, $ptr + 1, null, true );
			if ( false !== $name_ptr
				&& T_STRING === $tokens[ $name_ptr ]['code']
				&& $tokens[ $name_ptr ]['content'] === $name
			) {
				return $ptr;
			}
		}

		return false;
	}

	/**
	 * Checks a function/closure for a typehint on the first parameter.
	 *
	 * Only the first parameter is checked. `mixed` is explicitly allowed because
	 * it accurately documents that any type may arrive. Parameters after the first
	 * are not checked.
	 *
	 * @param int $function_token Position of the T_FUNCTION / T_CLOSURE / T_FN token.
	 */
	private function check_for_typehints( int $function_token ) {
		$tokens = $this->phpcsFile->getTokens();
		$params = $this->phpcsFile->getMethodParameters( $function_token );

		if ( empty( $params ) ) {
			return;
		}

		$param = $params[0];

		if ( empty( $param['type_hint'] ) ) {
			return;
		}

		// `mixed` accurately reflects that any type may be passed; allow it.
		if ( 'mixed' === $param['type_hint'] ) {
			return;
		}

		$fix = $this->phpcsFile->addFixableError(
			'Typehints on filter callback parameters can cause fatal errors if the passed type changes. Use type checking within the function body instead.',
			$param['type_hint_token'],
			'ParameterTypehint'
		);

		if ( $fix ) {
			$this->phpcsFile->fixer->beginChangeset();
			for ( $i = $param['type_hint_token']; $i <= $param['type_hint_end_token']; $i++ ) {
				$this->phpcsFile->fixer->replaceToken( $i, '' );
			}
			$ptr = $param['type_hint_end_token'] + 1;
			while ( isset( $tokens[ $ptr ] ) && T_WHITESPACE === $tokens[ $ptr ]['code'] ) {
				$this->phpcsFile->fixer->replaceToken( $ptr, '' );
				$ptr++;
			}
			$this->phpcsFile->fixer->endChangeset();
		}
	}
}
