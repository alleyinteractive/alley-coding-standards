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
	 * Key positions already reported, so a variable passed to several calls
	 * doesn't report the same key twice.
	 *
	 * @var array<string, array<int, bool>>
	 */
	private array $reported = [];

	/**
	 * Checks the `$extra_attributes` argument.
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The function name which was matched.
	 * @param array  $parameters      Information about the parameters.
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
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
		if ( $this->is_safe_key( $start, $end ) || isset( $this->reported[ $this->phpcsFile->getFilename() ][ $start ] ) ) {
			return;
		}

		$this->reported[ $this->phpcsFile->getFilename() ][ $start ] = true;

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
		$name     = $this->tokens[ $var_ptr ]['content'];
		$function = Conditions::getLastCondition( $this->phpcsFile, $var_ptr, [ T_FUNCTION, T_CLOSURE ] );
		$start    = 0;

		if ( false !== $function ) {
			$start = $this->tokens[ $function ]['scope_opener'] + 1;

			// Parameters and closure `use` variables come from outside this scope.
			for ( $i = $this->tokens[ $function ]['parenthesis_opener']; $i < $start; $i++ ) {
				if ( T_VARIABLE === $this->tokens[ $i ]['code'] && $name === $this->tokens[ $i ]['content'] ) {
					return sprintf( '%s is passed in from outside this function', $name );
				}
			}
		}

		$assigned = false;

		for ( $i = $start; $i < $var_ptr; $i++ ) {
			// Variables in nested functions and classes belong to another scope.
			if ( isset( $this->tokens[ $i ]['scope_closer'] ) && $this->tokens[ $i ]['scope_closer'] < $var_ptr && in_array( $this->tokens[ $i ]['code'], [ T_FUNCTION, T_CLOSURE, T_FN, T_CLASS, T_ANON_CLASS, T_TRAIT, T_INTERFACE, T_ENUM ], true ) ) {
				$i = $this->tokens[ $i ]['scope_closer'];
				continue;
			}

			if ( T_VARIABLE !== $this->tokens[ $i ]['code'] || $name !== $this->tokens[ $i ]['content'] ) {
				continue;
			}

			$write = $this->check_variable_use( $i, $var_ptr );

			if ( false === $write ) {
				return sprintf( '%s is set on line %d in a way that can\'t be traced', $name, $this->tokens[ $i ]['line'] );
			}

			$assigned = $assigned || $write;
		}

		return $assigned ? null : sprintf( '%s is not assigned in this scope', $name );
	}

	/**
	 * Checks one use of the traced variable.
	 *
	 * @param int $ptr   A T_VARIABLE token matching the traced variable.
	 * @param int $limit The use being traced; statements containing it are skipped.
	 * @return bool|null True for a checked write, null for a read, false for a write that can't be traced.
	 */
	private function check_variable_use( int $ptr, int $limit ) {
		$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $ptr - 1, null, true );
		$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $ptr + 1, null, true );

		// References, globals, statics, and foreach targets can change it from elsewhere.
		if ( in_array( $this->tokens[ $prev ]['code'], [ T_BITWISE_AND, T_GLOBAL, T_STATIC ], true ) || $this->is_foreach_target( $ptr ) ) {
			return false;
		}

		// `$attrs = ...` or `$attrs += ...`.
		if ( isset( Tokens::$assignmentTokens[ $this->tokens[ $next ]['code'] ] ) ) {
			$end = $this->phpcsFile->findEndOfStatement( $next + 1 );

			// `$attrs = array_merge( $attrs, ... )` while tracing that inner `$attrs`.
			if ( $end >= $limit ) {
				return null;
			}

			$first = $this->phpcsFile->findNext( Tokens::$emptyTokens, $next + 1, $end + 1, true );
			$last  = $this->phpcsFile->findPrevious( Tokens::$emptyTokens + [ T_SEMICOLON => T_SEMICOLON ], $end, $next, true );

			return false !== $first && null === $this->check_value( $first, $last );
		}

		if ( T_OPEN_SQUARE_BRACKET !== $this->tokens[ $next ]['code'] ) {
			return null;
		}

		// `$attrs[ key ]...`: a write only if the bracket chain ends in an assignment.
		$key_open  = $next;
		$key_close = $this->tokens[ $key_open ]['bracket_closer'];
		$after     = $this->phpcsFile->findNext( Tokens::$emptyTokens, $key_close + 1, null, true );

		while ( T_OPEN_SQUARE_BRACKET === $this->tokens[ $after ]['code'] ) {
			$after = $this->phpcsFile->findNext( Tokens::$emptyTokens, $this->tokens[ $after ]['bracket_closer'] + 1, null, true );
		}

		if ( ! isset( Tokens::$assignmentTokens[ $this->tokens[ $after ]['code'] ] ) ) {
			return null;
		}

		$key_start = $this->phpcsFile->findNext( Tokens::$emptyTokens, $key_open + 1, $key_close, true );

		// `$attrs[] = ...` appends a numeric key.
		if ( false !== $key_start ) {
			$this->check_key( $key_start, $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $key_close - 1, null, true ) );
		}

		return true;
	}

	/**
	 * Whether a variable is the key or value target of a foreach.
	 *
	 * @param int $ptr The variable token.
	 * @return bool
	 */
	private function is_foreach_target( int $ptr ): bool {
		if ( empty( $this->tokens[ $ptr ]['nested_parenthesis'] ) ) {
			return false;
		}

		$open  = array_key_last( $this->tokens[ $ptr ]['nested_parenthesis'] );
		$owner = $this->tokens[ $open ]['parenthesis_owner'] ?? null;

		if ( null === $owner || T_FOREACH !== $this->tokens[ $owner ]['code'] ) {
			return false;
		}

		$as = $this->phpcsFile->findNext( T_AS, $open + 1, $this->tokens[ $open ]['parenthesis_closer'] );

		return false !== $as && $ptr > $as;
	}
}
