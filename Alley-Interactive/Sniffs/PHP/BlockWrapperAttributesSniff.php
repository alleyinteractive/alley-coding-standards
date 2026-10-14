<?php
/**
 * BlockWrapperAttributesSniff class file
 *
 * @package Alley\CodingStandards
 */

namespace Alley\Sniffs\PHP;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Arrays;
use PHPCSUtils\Utils\Conditions;
use PHPCSUtils\Utils\PassedParameters;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Flags dynamic attribute names passed to get_block_wrapper_attributes().
 *
 * Core escapes attribute values with esc_attr() but outputs attribute names
 * as-is, so every name must be a string literal or wrapped in sanitize_key().
 * Variables and array_merge() arguments are traced back through their writes in
 * the current scope. Anything that can't be traced (a parameter, global, or
 * function return) is flagged so a reviewer has to confirm the names are safe.
 *
 * Error codes:
 *  - Alley.PHP.BlockWrapperAttributes.DynamicAttributeName
 *  - Alley.PHP.BlockWrapperAttributes.UnverifiableAttributes
 */
class BlockWrapperAttributesSniff extends AbstractFunctionParameterSniff {

	/**
	 * Functions this sniff checks.
	 *
	 * @var array<string, bool>
	 */
	protected $target_functions = [
		'get_block_wrapper_attributes' => true,
	];

	/**
	 * Variable use kinds returned by check_variable_use().
	 */
	private const USE_READ        = 0;
	private const USE_ASSIGN      = 1;
	private const USE_MODIFY      = 2;
	private const USE_UNTRACEABLE = 3;

	/**
	 * Functions that only read an array passed to them, so passing the traced variable isn't a write.
	 *
	 * @var array<string, bool>
	 */
	private const READ_ONLY_FUNCTIONS = [
		'array_key_exists'             => true,
		'array_keys'                   => true,
		'array_merge'                  => true,
		'array_values'                 => true,
		'count'                        => true,
		'get_block_wrapper_attributes' => true,
		'implode'                      => true,
		'in_array'                     => true,
		'is_array'                     => true,
		'wp_json_encode'               => true,
	];

	/**
	 * The file and fixer pass that $reported and $traced belong to.
	 *
	 * @var string
	 */
	private string $file_key = '';

	/**
	 * Key positions already reported, so a variable passed to several calls
	 * doesn't report the same key twice.
	 *
	 * @var array<int, bool>
	 */
	private array $reported = [];

	/**
	 * Trace results by variable position, so repeated self-merges aren't
	 * re-traced exponentially.
	 *
	 * @var array<int, string|null>
	 */
	private array $traced = [];

	/**
	 * Checks the `$extra_attributes` argument.
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The function name which was matched.
	 * @param array  $parameters      Information about the parameters.
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		// Token positions change between files and phpcbf passes.
		$file_key = $this->phpcsFile->getFilename() . ':' . ( $this->phpcsFile->fixer->enabled ? $this->phpcsFile->fixer->loops : 0 );

		if ( $file_key !== $this->file_key ) {
			$this->file_key = $file_key;
			$this->reported = [];
			$this->traced   = [];
		}

		$param = PassedParameters::getParameterFromStack( $parameters, 1, 'extra_attributes' );

		if ( false === $param ) {
			return;
		}

		$first = $this->phpcsFile->findNext( Tokens::$emptyTokens, $param['start'], $param['end'] + 1, true );
		$last  = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $param['end'], $param['start'] - 1, true );

		if ( false === $first ) {
			return;
		}

		$reason = $this->check_value( $first, $last );

		if ( null !== $reason ) {
			$this->phpcsFile->addError(
				'Cannot verify the attribute names passed to get_block_wrapper_attributes() because %s. Attribute names are not escaped; build the array in this scope with string literal or sanitize_key() keys.',
				$first,
				'UnverifiableAttributes',
				[ $reason ]
			);
		}
	}

	/**
	 * Checks an attributes value: an array literal, a traced variable, or array_merge() of those.
	 *
	 * Unsafe keys are reported where they're written.
	 *
	 * @param int $first First non-empty token of the value.
	 * @param int $last  Last non-empty token of the value.
	 * @return string|null Why the value can't be verified, or null if it can.
	 */
	private function check_value( int $first, int $last ): ?string {
		$array = Arrays::getOpenClose( $this->phpcsFile, $first );

		if ( false !== $array && $array['closer'] === $last ) {
			return $this->check_array_literal( $first );
		}

		if ( $first === $last && T_VARIABLE === $this->tokens[ $first ]['code'] ) {
			return $this->check_variable( $first );
		}

		$function = T_NS_SEPARATOR === $this->tokens[ $first ]['code']
			? $this->phpcsFile->findNext( Tokens::$emptyTokens, $first + 1, null, true )
			: $first;
		$open     = $this->phpcsFile->findNext( Tokens::$emptyTokens, $function + 1, null, true );

		if (
			T_STRING === $this->tokens[ $function ]['code']
			&& 'array_merge' === strtolower( $this->tokens[ $function ]['content'] )
			&& T_OPEN_PARENTHESIS === $this->tokens[ $open ]['code']
			&& $this->tokens[ $open ]['parenthesis_closer'] === $last
		) {
			foreach ( PassedParameters::getParameters( $this->phpcsFile, $function ) as $arg ) {
				$arg_first = $this->phpcsFile->findNext( Tokens::$emptyTokens, $arg['start'], $arg['end'] + 1, true );
				$arg_last  = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $arg['end'], $arg['start'] - 1, true );

				if ( false === $arg_first ) {
					continue;
				}

				$reason = $this->check_value( $arg_first, $arg_last );

				if ( null !== $reason ) {
					return $reason;
				}
			}

			return null;
		}

		return sprintf( '"%s" is not an array literal, a variable, or array_merge() of those', $this->phpcsFile->getTokensAsString( $first, $last - $first + 1 ) );
	}

	/**
	 * Checks the keys of an array literal.
	 *
	 * @param int $array_ptr The `[` or `array` token.
	 * @return string|null Why the array can't be verified, or null if it can.
	 */
	private function check_array_literal( int $array_ptr ): ?string {
		foreach ( PassedParameters::getParameters( $this->phpcsFile, $array_ptr ) as $item ) {
			$item_start = $this->phpcsFile->findNext( Tokens::$emptyTokens, $item['start'], $item['end'] + 1, true );

			if ( false === $item_start ) {
				continue;
			}

			if ( T_ELLIPSIS === $this->tokens[ $item_start ]['code'] ) {
				return 'it spreads in another array on line ' . $this->tokens[ $item_start ]['line'];
			}

			$arrow = Arrays::getDoubleArrowPtr( $this->phpcsFile, $item['start'], $item['end'] );

			if ( false !== $arrow ) {
				$this->check_key( $item_start, $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $arrow - 1, null, true ) );
			}
		}

		return null;
	}

	/**
	 * Flags a key unless it's a literal or wrapped in sanitize_key().
	 *
	 * @param int $start First non-empty token of the key.
	 * @param int $end   Last non-empty token of the key.
	 */
	private function check_key( int $start, int $end ): void {
		if ( $this->is_safe_key( $start, $end ) || isset( $this->reported[ $start ] ) ) {
			return;
		}

		$this->reported[ $start ] = true;

		$this->phpcsFile->addError(
			'Attribute names passed to get_block_wrapper_attributes() are not escaped. Use a string literal or wrap the name in sanitize_key(). Found: %s',
			$start,
			'DynamicAttributeName',
			[ $this->phpcsFile->getTokensAsString( $start, $end - $start + 1 ) ]
		);
	}

	/**
	 * Whether a key is a literal or a single sanitize_key() call.
	 *
	 * @param int $start First non-empty token of the key.
	 * @param int $end   Last non-empty token of the key.
	 * @return bool
	 */
	private function is_safe_key( int $start, int $end ): bool {
		if ( $start === $end ) {
			return in_array( $this->tokens[ $start ]['code'], [ T_CONSTANT_ENCAPSED_STRING, T_LNUMBER ], true );
		}

		if ( T_NS_SEPARATOR === $this->tokens[ $start ]['code'] ) {
			$start = $this->phpcsFile->findNext( Tokens::$emptyTokens, $start + 1, null, true );
		}

		if ( T_STRING !== $this->tokens[ $start ]['code'] || 'sanitize_key' !== strtolower( $this->tokens[ $start ]['content'] ) ) {
			return false;
		}

		$open = $this->phpcsFile->findNext( Tokens::$emptyTokens, $start + 1, null, true );

		return T_OPEN_PARENTHESIS === $this->tokens[ $open ]['code']
			&& $this->tokens[ $open ]['parenthesis_closer'] === $end;
	}

	/**
	 * Traces a variable through its writes earlier in the same scope.
	 *
	 * @param int $var_ptr The variable token.
	 * @return string|null Why the variable can't be verified, or null if it can.
	 */
	private function check_variable( int $var_ptr ): ?string {
		if ( ! array_key_exists( $var_ptr, $this->traced ) ) {
			$this->traced[ $var_ptr ] = $this->trace_variable( $var_ptr );
		}

		return $this->traced[ $var_ptr ];
	}

	/**
	 * Traces a variable through its writes earlier in the same scope, uncached.
	 *
	 * @param int $var_ptr The variable token.
	 * @return string|null Why the variable can't be verified, or null if it can.
	 */
	private function trace_variable( int $var_ptr ): ?string {
		$name     = $this->tokens[ $var_ptr ]['content'];
		$function = Conditions::getLastCondition( $this->phpcsFile, $var_ptr, [ T_FUNCTION, T_CLOSURE ] );
		$start    = 0;

		if ( false !== $function ) {
			$start = $this->tokens[ $function ]['scope_opener'] + 1;

			if ( $this->is_parameter( $function, $name ) ) {
				return sprintf( '%s is passed in from outside this function', $name );
			}
		}

		$assigned = false;

		for ( $i = $start; $i < $var_ptr; $i++ ) {
			if ( isset( $this->tokens[ $i ]['scope_closer'] ) && in_array( $this->tokens[ $i ]['code'], [ T_FUNCTION, T_CLOSURE, T_FN, T_CLASS, T_ANON_CLASS, T_TRAIT, T_INTERFACE, T_ENUM ], true ) ) {
				// A closure that captures it by reference can change it whenever the closure is called.
				if ( T_CLOSURE === $this->tokens[ $i ]['code'] && $this->captures_by_reference( $i, $name ) ) {
					return sprintf( '%s is captured by reference on line %d', $name, $this->tokens[ $i ]['line'] );
				}

				// Variables in nested functions and classes belong to another scope.
				if ( $this->tokens[ $i ]['scope_closer'] < $var_ptr ) {
					$i = $this->tokens[ $i ]['scope_closer'];
					continue;
				}

				// Arrow functions aren't conditions, so a parameter can shadow the captured outer variable.
				if ( T_FN === $this->tokens[ $i ]['code'] && $this->is_parameter( $i, $name ) ) {
					return sprintf( '%s is passed in from outside this function', $name );
				}
			}

			if ( $this->can_write_any_variable( $i ) ) {
				return sprintf( '%s can be overwritten by the code on line %d', $name, $this->tokens[ $i ]['line'] );
			}

			if ( T_VARIABLE !== $this->tokens[ $i ]['code'] || $name !== $this->tokens[ $i ]['content'] ) {
				continue;
			}

			$use = $this->check_variable_use( $i, $var_ptr );

			if ( self::USE_UNTRACEABLE === $use ) {
				return sprintf( '%s is set on line %d in a way that can\'t be traced', $name, $this->tokens[ $i ]['line'] );
			}

			if ( self::USE_ASSIGN !== $use ) {
				// Element writes and compound assignments keep existing keys, so they don't count.
				continue;
			}

			// An assignment inside a block the call isn't in, like an `if`, may not run.
			if ( [] === array_diff_key( $this->tokens[ $i ]['conditions'], $this->tokens[ $var_ptr ]['conditions'] ) ) {
				$assigned = true;
			} elseif ( ! $assigned ) {
				$branch_line = $this->tokens[ $i ]['line'];
			}
		}

		if ( $assigned ) {
			return null;
		}

		return isset( $branch_line )
			? sprintf( '%s is only assigned on line %d inside a block that may not run', $name, $branch_line )
			: sprintf( '%s is not assigned in this scope', $name );
	}

	/**
	 * Whether a function's parameters or closure `use` list include a variable.
	 *
	 * @param int    $function The function, closure, or arrow function token.
	 * @param string $name     The variable name.
	 * @return bool
	 */
	private function is_parameter( int $function, string $name ): bool {
		for ( $i = $this->tokens[ $function ]['parenthesis_opener']; $i < $this->tokens[ $function ]['scope_opener']; $i++ ) {
			if ( T_VARIABLE === $this->tokens[ $i ]['code'] && $name === $this->tokens[ $i ]['content'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a closure's `use` list captures a variable by reference.
	 *
	 * @param int    $closure The closure token.
	 * @param string $name    The variable name.
	 * @return bool
	 */
	private function captures_by_reference( int $closure, string $name ): bool {
		for ( $i = $this->tokens[ $closure ]['parenthesis_closer']; $i < $this->tokens[ $closure ]['scope_opener']; $i++ ) {
			if ( T_VARIABLE === $this->tokens[ $i ]['code'] && $name === $this->tokens[ $i ]['content'] ) {
				return T_BITWISE_AND === $this->tokens[ $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $i - 1, null, true ) ]['code'];
			}
		}

		return false;
	}


	/**
	 * Checks one use of the traced variable.
	 *
	 * Only known-safe forms are allowed: assigning a traceable value, writing an element with a checked key,
	 * and reading. Any other use could replace the array, so it can't be traced.
	 *
	 * @param int $ptr   A T_VARIABLE token matching the traced variable.
	 * @param int $limit The use being traced; statements containing it are skipped.
	 * @return int One of the USE_* constants.
	 */
	private function check_variable_use( int $ptr, int $limit ): int {
		$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $ptr - 1, null, true );
		$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $ptr + 1, null, true );

		// `$attrs = ...`, `$attrs += ...`, or `$attrs ??= ...`.
		if ( in_array( $this->tokens[ $next ]['code'], [ T_EQUAL, T_PLUS_EQUAL, T_COALESCE_EQUAL ], true ) && ! in_array( $this->tokens[ $prev ]['code'], [ T_BITWISE_AND, T_DOLLAR, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON ], true ) ) {
			$end = $this->phpcsFile->findEndOfStatement( $next + 1 );

			// `$attrs = array_merge( $attrs, ... )` while tracing that inner `$attrs`.
			if ( $end >= $limit ) {
				return self::USE_READ;
			}

			$first = $this->phpcsFile->findNext( Tokens::$emptyTokens, $next + 1, $end + 1, true );
			$last  = $this->phpcsFile->findPrevious( Tokens::$emptyTokens + [ T_SEMICOLON => T_SEMICOLON ], $end, $next, true );

			if ( false === $first || null !== $this->check_value( $first, $last ) ) {
				return self::USE_UNTRACEABLE;
			}

			// `+=` and `??=` keep the existing keys.
			return T_EQUAL === $this->tokens[ $next ]['code'] ? self::USE_ASSIGN : self::USE_MODIFY;
		}

		if ( T_OPEN_SQUARE_BRACKET !== $this->tokens[ $next ]['code'] ) {
			return $this->is_read( $ptr, $ptr ) ? self::USE_READ : self::USE_UNTRACEABLE;
		}

		// `$attrs[ key ]...`: only that key can change, so a read or a write with a safe key is fine.
		$key_open  = $next;
		$key_close = $this->tokens[ $key_open ]['bracket_closer'];
		$end       = $key_close;
		$after     = $this->phpcsFile->findNext( Tokens::$emptyTokens, $end + 1, null, true );

		while ( T_OPEN_SQUARE_BRACKET === $this->tokens[ $after ]['code'] ) {
			$end   = $this->tokens[ $after ]['bracket_closer'];
			$after = $this->phpcsFile->findNext( Tokens::$emptyTokens, $end + 1, null, true );
		}

		$is_write = in_array( $this->tokens[ $prev ]['code'], [ T_INC, T_DEC ], true )
			|| in_array( $this->tokens[ $after ]['code'], [ T_INC, T_DEC ], true )
			|| ( T_DOUBLE_ARROW !== $this->tokens[ $after ]['code'] && isset( Tokens::$assignmentTokens[ $this->tokens[ $after ]['code'] ] ) );

		if ( ! $is_write && $this->is_read( $ptr, $end ) ) {
			return self::USE_READ;
		}

		$key_start = $this->phpcsFile->findNext( Tokens::$emptyTokens, $key_open + 1, $key_close, true );

		// `$attrs[] = ...` appends a numeric key.
		if ( false !== $key_start ) {
			$this->check_key( $key_start, $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $key_close - 1, null, true ) );
		}

		return self::USE_MODIFY;
	}

	/**
	 * Whether a variable or element is only read where it appears.
	 *
	 * Reads are an operand of an operator, a value being assigned, returned, or echoed, a condition, the subject of
	 * a foreach, or an argument to isset(), empty(), unset(), or a read-only function. Anything else, like a
	 * function argument, a list() or foreach target, a reference, or a global, could be a write.
	 *
	 * @param int $start The variable token.
	 * @param int $end   The last token of the variable or element.
	 * @return bool
	 */
	private function is_read( int $start, int $end ): bool {
		$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $start - 1, null, true );
		$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $end + 1, null, true );

		$operators = Tokens::$arithmeticTokens + Tokens::$comparisonTokens + Tokens::$booleanOperators + [
			T_STRING_CONCAT => T_STRING_CONCAT,
			T_INLINE_THEN   => T_INLINE_THEN,
			T_INLINE_ELSE   => T_INLINE_ELSE,
		];
		$before    = $operators + Tokens::$assignmentTokens + [
			T_BOOLEAN_NOT => T_BOOLEAN_NOT,
			T_RETURN      => T_RETURN,
			T_ECHO        => T_ECHO,
			T_PRINT       => T_PRINT,
			T_FN_ARROW    => T_FN_ARROW,
		];
		$after     = $operators + [
			T_OBJECT_OPERATOR          => T_OBJECT_OPERATOR,
			T_NULLSAFE_OBJECT_OPERATOR => T_NULLSAFE_OBJECT_OPERATOR,
		];

		// `[ 'key' => $attrs ]` may be a list() target.
		unset( $before[ T_DOUBLE_ARROW ] );

		if ( isset( $before[ $this->tokens[ $prev ]['code'] ] ) || isset( $after[ $this->tokens[ $next ]['code'] ] ) ) {
			return true;
		}

		if (
			! in_array( $this->tokens[ $prev ]['code'], [ T_OPEN_PARENTHESIS, T_COMMA ], true )
			|| ! in_array( $this->tokens[ $next ]['code'], [ T_CLOSE_PARENTHESIS, T_COMMA, T_AS ], true )
			|| empty( $this->tokens[ $start ]['nested_parenthesis'] )
		) {
			return false;
		}

		$open = array_key_last( $this->tokens[ $start ]['nested_parenthesis'] );

		// `if ( $attrs )` or `foreach ( $attrs as ... )`.
		if ( isset( $this->tokens[ $open ]['parenthesis_owner'] ) ) {
			$owner = $this->tokens[ $this->tokens[ $open ]['parenthesis_owner'] ]['code'];

			return T_OPEN_PARENTHESIS === $this->tokens[ $prev ]['code'] && (
				( T_FOREACH === $owner && T_AS === $this->tokens[ $next ]['code'] )
				|| ( in_array( $owner, [ T_IF, T_ELSEIF, T_WHILE, T_SWITCH, T_MATCH ], true ) && T_CLOSE_PARENTHESIS === $this->tokens[ $next ]['code'] )
			);
		}

		$callee = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $open - 1, null, true );

		if ( in_array( $this->tokens[ $callee ]['code'], [ T_ISSET, T_EMPTY, T_UNSET ], true ) ) {
			return true;
		}

		// Methods and constructors can't be looked up, so only plain function calls can be read-only.
		$before_callee = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $callee - 1, null, true );

		return T_STRING === $this->tokens[ $callee ]['code']
			&& isset( self::READ_ONLY_FUNCTIONS[ strtolower( $this->tokens[ $callee ]['content'] ) ] )
			&& ! in_array( $this->tokens[ $before_callee ]['code'], [ T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW, T_FUNCTION ], true );
	}

	/**
	 * Whether a token is `extract()`, a variable variable, or an include, which can write any variable in scope.
	 *
	 * @param int $ptr The token.
	 * @return bool
	 */
	private function can_write_any_variable( int $ptr ): bool {
		$code = $this->tokens[ $ptr ]['code'];

		if ( in_array( $code, [ T_DOLLAR, T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE ], true ) ) {
			return true;
		}

		if ( T_STRING !== $code || 'extract' !== strtolower( $this->tokens[ $ptr ]['content'] ) ) {
			return false;
		}

		$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $ptr - 1, null, true );
		$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $ptr + 1, null, true );

		return T_OPEN_PARENTHESIS === $this->tokens[ $next ]['code']
			&& ! in_array( $this->tokens[ $prev ]['code'], [ T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW ], true );
	}
}
