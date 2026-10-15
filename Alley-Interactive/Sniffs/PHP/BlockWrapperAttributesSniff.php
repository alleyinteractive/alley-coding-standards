<?php
/**
 * BlockWrapperAttributesSniff class file
 *
 * @package Alley\CodingStandards
 */

namespace Alley\Sniffs\PHP;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Arrays;
use PHPCSUtils\Utils\Lists;
use PHPCSUtils\Utils\PassedParameters;
use WordPressCS\WordPress\Sniff;

/**
 * Flags dynamic array keys passed to get_block_wrapper_attributes().
 *
 * Keys become attribute names and are not escaped, unlike values. What each
 * error code covers is documented in the README.
 *
 * Variables are traced with a linear token scan that has no branch awareness,
 * so an unsafe assignment in any branch is flagged. Anything the scan cannot
 * analyze is flagged rather than ignored.
 *
 * Error codes:
 *  - Alley.PHP.BlockWrapperAttributes.UnsafeKey
 *  - Alley.PHP.BlockWrapperAttributes.UnverifiableKeys
 */
class BlockWrapperAttributesSniff extends Sniff {

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

		if ( 'get_block_wrapper_attributes' !== strtolower( $tokens[ $stackPtr ]['content'] ) ) {
			return;
		}

		// Skip method calls, function declarations, and functions in other namespaces.
		$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 1, null, true );
		if ( false !== $prev ) {
			if ( in_array( $tokens[ $prev ]['code'], [ T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW ], true ) ) {
				return;
			}

			if ( T_NS_SEPARATOR === $tokens[ $prev ]['code'] ) {
				$before_ns = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $prev - 1, null, true );
				if ( false !== $before_ns && in_array( $tokens[ $before_ns ]['code'], [ T_STRING, T_NAMESPACE ], true ) ) {
					return;
				}
			}
		}

		// Must be a function call: next non-empty token must be '('.
		$open_paren = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true );
		if ( false === $open_paren || T_OPEN_PARENTHESIS !== $tokens[ $open_paren ]['code'] ) {
			return;
		}

		$param = PassedParameters::getParameter( $this->phpcsFile, $stackPtr, 1, 'extra_attributes' );
		if ( false === $param ) {
			return;
		}

		$first = $this->phpcsFile->findNext( Tokens::$emptyTokens, $param['start'], $param['end'] + 1, true );
		$last  = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $param['end'], $param['start'] - 1, true );
		if ( false === $first || false === $last ) {
			return;
		}

		// Inline array: check its keys directly.
		$array_closer = $this->get_array_closer( $first );
		if ( $array_closer === $last ) {
			$this->check_array_keys( $first );
			return;
		}

		// Plain variable: trace assignments to it in the enclosing scope.
		if ( T_VARIABLE === $tokens[ $first ]['code'] && $first === $last ) {
			$this->check_variable( $first, $stackPtr );
			return;
		}

		$this->add_unverifiable_error( $first );
	}

	/**
	 * Returns the closing token of an array literal opened at the given position.
	 *
	 * @param int $ptr Position of a T_OPEN_SHORT_ARRAY or T_ARRAY token.
	 * @return int|false Position of the closer, or false if not an array literal.
	 */
	private function get_array_closer( int $ptr ) {
		$tokens = $this->phpcsFile->getTokens();

		if ( T_OPEN_SHORT_ARRAY === $tokens[ $ptr ]['code'] ) {
			return $tokens[ $ptr ]['bracket_closer'];
		}

		if ( T_ARRAY === $tokens[ $ptr ]['code'] && isset( $tokens[ $ptr ]['parenthesis_closer'] ) ) {
			return $tokens[ $ptr ]['parenthesis_closer'];
		}

		return false;
	}

	/**
	 * Checks every top-level key in an array literal.
	 *
	 * Items without a key get an integer key and are safe. Spread items
	 * (`...$other`) can contain any key and are unverifiable.
	 *
	 * @param int $array_ptr Position of the T_OPEN_SHORT_ARRAY or T_ARRAY token.
	 */
	private function check_array_keys( int $array_ptr ) {
		$tokens = $this->phpcsFile->getTokens();
		$items  = PassedParameters::getParameters( $this->phpcsFile, $array_ptr );

		foreach ( $items as $item ) {
			$first = $this->phpcsFile->findNext( Tokens::$emptyTokens, $item['start'], $item['end'] + 1, true );
			if ( false === $first ) {
				continue;
			}

			if ( T_ELLIPSIS === $tokens[ $first ]['code'] ) {
				$this->add_unverifiable_error( $first );
				continue;
			}

			$arrow = Arrays::getDoubleArrowPtr( $this->phpcsFile, $item['start'], $item['end'] );
			if ( false === $arrow ) {
				continue;
			}

			$this->check_key( $item['start'], $arrow - 1 );
		}
	}

	/**
	 * Flags a key expression unless it is a literal or wrapped in sanitize_key().
	 *
	 * @param int $start First token of the key expression.
	 * @param int $end   Last token of the key expression.
	 */
	private function check_key( int $start, int $end ) {
		$tokens = $this->phpcsFile->getTokens();
		$first  = $this->phpcsFile->findNext( Tokens::$emptyTokens, $start, $end + 1, true );
		$last   = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $end, $start - 1, true );

		if ( false === $first || false === $last ) {
			return;
		}

		// String literals without interpolation, and integers.
		if ( $first === $last && in_array( $tokens[ $first ]['code'], [ T_CONSTANT_ENCAPSED_STRING, T_LNUMBER ], true ) ) {
			return;
		}

		// sanitize_key( ... ) wrapping the entire key.
		$name = $first;
		if ( T_NS_SEPARATOR === $tokens[ $name ]['code'] ) {
			$name = $this->phpcsFile->findNext( Tokens::$emptyTokens, $name + 1, $last + 1, true );
		}

		if ( false !== $name && T_STRING === $tokens[ $name ]['code'] && 'sanitize_key' === strtolower( $tokens[ $name ]['content'] ) ) {
			$open = $this->phpcsFile->findNext( Tokens::$emptyTokens, $name + 1, $last + 1, true );
			if ( false !== $open && T_OPEN_PARENTHESIS === $tokens[ $open ]['code'] && $tokens[ $open ]['parenthesis_closer'] === $last ) {
				return;
			}
		}

		$this->phpcsFile->addError(
			'Array keys passed to get_block_wrapper_attributes() become attribute names and are not escaped. Use a string literal or wrap the key in sanitize_key().',
			$first,
			'UnsafeKey'
		);
	}

	/**
	 * Checks every assignment to a variable in scope before it is passed to the call.
	 *
	 * @param int $var_ptr  Position of the T_VARIABLE passed as the argument.
	 * @param int $call_ptr Position of the get_block_wrapper_attributes token.
	 */
	private function check_variable( int $var_ptr, int $call_ptr ) {
		$tokens   = $this->phpcsFile->getTokens();
		$var_name = $tokens[ $var_ptr ]['content'];
		$scope    = $this->get_enclosing_function( $call_ptr );

		$scan_start = 0;

		if ( false !== $scope ) {
			$scan_start = $tokens[ $scope ]['scope_opener'] + 1;

			// A parameter or closure `use` variable comes from outside this scope.
			if ( $this->declares_variable( $scope, $var_name ) ) {
				$this->add_unverifiable_error( $var_ptr );
				return;
			}
		}

		// Arrow functions are not tracked as conditions, so check their parameters separately.
		for ( $i = $scan_start; $i < $call_ptr; $i++ ) {
			if (
				T_FN === $tokens[ $i ]['code']
				&& isset( $tokens[ $i ]['scope_closer'] )
				&& $tokens[ $i ]['scope_closer'] > $call_ptr
				&& $this->get_enclosing_function( $i ) === $scope
				&& $this->declares_variable( $i, $var_name )
			) {
				$this->add_unverifiable_error( $var_ptr );
				return;
			}
		}

		// In a loop, assignments after the call still run before its next iteration.
		$scan_end = $this->get_scan_end( $call_ptr, $scope );

		$found_array_assignment = false;

		for ( $i = $scan_start; $i < $scan_end; $i++ ) {
			// Destructuring (`[ $attrs ] = ...`, `list( $attrs ) = ...`) can assign anything.
			if ( in_array( $tokens[ $i ]['code'], [ T_LIST, T_OPEN_SHORT_ARRAY ], true ) && $this->get_enclosing_function( $i ) === $scope ) {
				$list = Lists::getOpenClose( $this->phpcsFile, $i );

				if ( false !== $list && false !== $this->phpcsFile->findNext( T_VARIABLE, $list['opener'] + 1, $list['closer'], false, $var_name ) ) {
					$this->add_unverifiable_error( $var_ptr );
					return;
				}
			}

			if ( T_VARIABLE !== $tokens[ $i ]['code'] || $var_name !== $tokens[ $i ]['content'] ) {
				continue;
			}

			// Skip variables belonging to a nested function or closure.
			if ( $this->get_enclosing_function( $i ) !== $scope ) {
				continue;
			}

			$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $i - 1, null, true );
			$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $i + 1, null, true );

			// Static properties (`self::$attrs`) are a different variable.
			if ( false !== $prev && T_DOUBLE_COLON === $tokens[ $prev ]['code'] ) {
				continue;
			}

			// References, globals, and foreach variables can hold anything.
			if ( false !== $prev && in_array( $tokens[ $prev ]['code'], [ T_BITWISE_AND, T_AS ], true ) ) {
				$this->add_unverifiable_error( $var_ptr );
				return;
			}

			$statement_start = $this->phpcsFile->findStartOfStatement( $i );
			if ( T_GLOBAL === $tokens[ $statement_start ]['code'] ) {
				$this->add_unverifiable_error( $var_ptr );
				return;
			}

			if ( false !== $prev && T_DOUBLE_ARROW === $tokens[ $prev ]['code'] && $this->is_in_foreach( $i ) ) {
				$this->add_unverifiable_error( $var_ptr );
				return;
			}

			// Passed whole to another function, which may change it by reference (e.g. parse_str()).
			if ( $this->is_function_call_argument( $i ) ) {
				$this->add_unverifiable_error( $var_ptr );
				return;
			}

			if ( false === $next ) {
				continue;
			}

			// Whole-variable assignment: `$attrs = [ ... ];` is checked, anything else is unverifiable.
			if ( T_EQUAL === $tokens[ $next ]['code'] ) {
				$value = $this->phpcsFile->findNext( Tokens::$emptyTokens, $next + 1, null, true );
				$close = false !== $value ? $this->get_array_closer( $value ) : false;
				$after = false !== $close ? $this->phpcsFile->findNext( Tokens::$emptyTokens, $close + 1, null, true ) : false;

				if ( false === $after || ! in_array( $tokens[ $after ]['code'], [ T_SEMICOLON, T_CLOSE_TAG ], true ) ) {
					$this->add_unverifiable_error( $var_ptr );
					return;
				}

				$found_array_assignment = true;
				$this->check_array_keys( $value );
				continue;
			}

			// Compound assignment to the whole variable (`+=`, `??=`, etc.).
			if ( isset( Tokens::$assignmentTokens[ $tokens[ $next ]['code'] ] ) ) {
				$this->add_unverifiable_error( $var_ptr );
				return;
			}

			// Key assignment: `$attrs[ <key> ] = ...;`.
			if ( T_OPEN_SQUARE_BRACKET === $tokens[ $next ]['code'] ) {
				$key_open  = $next;
				$key_close = $tokens[ $key_open ]['bracket_closer'];

				// Skip over any nested keys to find what follows the chain.
				$after = $this->phpcsFile->findNext( Tokens::$emptyTokens, $key_close + 1, null, true );
				while ( false !== $after && T_OPEN_SQUARE_BRACKET === $tokens[ $after ]['code'] ) {
					$after = $this->phpcsFile->findNext( Tokens::$emptyTokens, $tokens[ $after ]['bracket_closer'] + 1, null, true );
				}

				// Reads are ignored.
				if ( false === $after || ! isset( Tokens::$assignmentTokens[ $tokens[ $after ]['code'] ] ) ) {
					continue;
				}

				// `$attrs[] = ...` gets an integer key.
				if ( $key_close === $key_open + 1 || false === $this->phpcsFile->findNext( Tokens::$emptyTokens, $key_open + 1, $key_close, true ) ) {
					continue;
				}

				$this->check_key( $key_open + 1, $key_close - 1 );
			}
		}

		if ( ! $found_array_assignment ) {
			$this->add_unverifiable_error( $var_ptr );
		}
	}

	/**
	 * Returns the innermost function or closure containing a token.
	 *
	 * @param int $ptr Token position.
	 * @return int|false Position of the T_FUNCTION / T_CLOSURE token, or false at file scope.
	 */
	private function get_enclosing_function( int $ptr ) {
		$tokens     = $this->phpcsFile->getTokens();
		$conditions = array_reverse( $tokens[ $ptr ]['conditions'], true );

		foreach ( $conditions as $condition_ptr => $code ) {
			if ( T_FUNCTION === $code || T_CLOSURE === $code ) {
				return $condition_ptr;
			}
		}

		return false;
	}

	/**
	 * Determines whether a function, closure, or arrow function declares a variable
	 * as a parameter or closure `use` variable.
	 *
	 * @param int    $function_ptr Position of the T_FUNCTION, T_CLOSURE, or T_FN token.
	 * @param string $var_name     Variable name, including the `$`.
	 * @return bool
	 */
	private function declares_variable( int $function_ptr, string $var_name ): bool {
		$tokens = $this->phpcsFile->getTokens();

		for ( $i = $tokens[ $function_ptr ]['parenthesis_opener']; $i < $tokens[ $function_ptr ]['scope_opener']; $i++ ) {
			if ( T_VARIABLE === $tokens[ $i ]['code'] && $var_name === $tokens[ $i ]['content'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the position to stop scanning for assignments.
	 *
	 * Outside a loop that is the call itself. Inside one, it is the end of the
	 * outermost loop in the same scope, since anything later in the loop body runs
	 * before the next iteration's call.
	 *
	 * @param int       $call_ptr Position of the get_block_wrapper_attributes token.
	 * @param int|false $scope    Position of the enclosing function, or false at file scope.
	 * @return int
	 */
	private function get_scan_end( int $call_ptr, $scope ): int {
		$tokens = $this->phpcsFile->getTokens();

		// Conditions are ordered outermost first.
		foreach ( $tokens[ $call_ptr ]['conditions'] as $condition_ptr => $code ) {
			if ( false !== $scope && $condition_ptr <= $scope ) {
				continue;
			}

			if ( in_array( $code, [ T_FOR, T_FOREACH, T_WHILE, T_DO ], true ) && isset( $tokens[ $condition_ptr ]['scope_closer'] ) ) {
				return $tokens[ $condition_ptr ]['scope_closer'];
			}
		}

		return $call_ptr;
	}

	/**
	 * Determines whether a variable is passed whole as an argument to a function,
	 * method, or constructor call, other than get_block_wrapper_attributes().
	 *
	 * @param int $ptr Position of the T_VARIABLE token.
	 * @return bool
	 */
	private function is_function_call_argument( int $ptr ): bool {
		$tokens = $this->phpcsFile->getTokens();

		if ( empty( $tokens[ $ptr ]['nested_parenthesis'] ) ) {
			return false;
		}

		// The variable must be the entire argument, not part of an expression or a key read.
		$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $ptr - 1, null, true );
		$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $ptr + 1, null, true );

		if ( false === $prev ) {
			return false;
		}

		// A named argument (`name: $attrs`) is preceded by T_PARAM_NAME and a colon.
		if ( T_COLON === $tokens[ $prev ]['code'] ) {
			$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $prev - 1, null, true );
			if ( false === $prev || T_PARAM_NAME !== $tokens[ $prev ]['code'] ) {
				return false;
			}

			$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $prev - 1, null, true );
		}

		if ( false === $prev || ! in_array( $tokens[ $prev ]['code'], [ T_OPEN_PARENTHESIS, T_COMMA ], true ) ) {
			return false;
		}

		if ( false === $next || ! in_array( $tokens[ $next ]['code'], [ T_CLOSE_PARENTHESIS, T_COMMA ], true ) ) {
			return false;
		}

		$openers = array_keys( $tokens[ $ptr ]['nested_parenthesis'] );
		$opener  = end( $openers );

		// Declarations, control structures, array(), and list() own their parentheses. Calls do not.
		if ( isset( $tokens[ $opener ]['parenthesis_owner'] ) ) {
			return false;
		}

		$callee = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $opener - 1, null, true );
		if ( false === $callee ) {
			return false;
		}

		if ( T_STRING === $tokens[ $callee ]['code'] ) {
			return 'get_block_wrapper_attributes' !== strtolower( $tokens[ $callee ]['content'] );
		}

		// Variable functions, `new static()`, and calls on the result of an expression.
		return in_array( $tokens[ $callee ]['code'], [ T_VARIABLE, T_STATIC, T_SELF, T_PARENT, T_CLOSE_PARENTHESIS, T_CLOSE_SQUARE_BRACKET, T_CLOSE_CURLY_BRACKET ], true );
	}

	/**
	 * Determines whether a token is inside the parentheses of a foreach statement.
	 *
	 * @param int $ptr Token position.
	 * @return bool
	 */
	private function is_in_foreach( int $ptr ): bool {
		$tokens = $this->phpcsFile->getTokens();

		if ( empty( $tokens[ $ptr ]['nested_parenthesis'] ) ) {
			return false;
		}

		foreach ( $tokens[ $ptr ]['nested_parenthesis'] as $opener => $closer ) {
			if ( isset( $tokens[ $opener ]['parenthesis_owner'] ) && T_FOREACH === $tokens[ $tokens[ $opener ]['parenthesis_owner'] ]['code'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Reports that the keys passed to the call cannot be verified.
	 *
	 * @param int $ptr Token position to report on.
	 */
	private function add_unverifiable_error( int $ptr ) {
		$this->phpcsFile->addError(
			'Could not verify the array keys passed to get_block_wrapper_attributes(). Keys become attribute names and are not escaped. Build the array in the same scope as the call, using string literal keys or sanitize_key().',
			$ptr,
			'UnverifiableKeys'
		);
	}
}
