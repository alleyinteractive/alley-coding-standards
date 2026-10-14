<?php
/**
 * BlockWrapperAttributesSniff class file
 *
 * @package Alley\CodingStandards
 */

namespace Alley\Sniffs\PHP;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Arrays;
use PHPCSUtils\Utils\PassedParameters;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Flags unsafe keys passed to get_block_wrapper_attributes().
 *
 * The get_block_wrapper_attributes() function escapes attribute values with
 * esc_attr(), but the array keys become attribute names and are output as-is.
 * Keys must be string or integer literals, or concatenations of literals and
 * sanitize_key() calls that start with a string literal containing a hyphen,
 * such as `'data-' . sanitize_key( $key )`. In a concatenation with
 * sanitize_key(), every literal may only contain letters, digits, `_` and `-`. A bare sanitize_key() call is not
 * enough: it still allows names like `onclick` and `style`, whose values
 * esc_attr() does not make safe.
 *
 * Anything the sniff cannot prove safe is an error. A false positive can be
 * suppressed with phpcs:ignore once someone has checked the keys, but a false
 * negative lets an unsafe key reach production without anyone noticing.
 *
 * Supported shapes for the passed array:
 *  - Inline literals, including spreads of other arrays.
 *  - array_merge() and array_replace() calls, checking each argument.
 * Anything else, such as a variable, a function parameter, an object property,
 * a helper's return value, a ternary or `??`, is reported as UnverifiableKeys.
 *
 * Where errors are reported (this is also where a phpcs:ignore must go):
 *  - Inline keys: on the key.
 *  - Anything else: on the expression that can't be checked.
 * Every message includes the line of the get_block_wrapper_attributes() call.
 *
 * Error codes:
 *  - Alley.PHP.BlockWrapperAttributes.UnescapedDynamicKey
 *  - Alley.PHP.BlockWrapperAttributes.UnverifiableKeys
 */
class BlockWrapperAttributesSniff extends AbstractFunctionParameterSniff {

	/**
	 * The group name for this group of functions.
	 *
	 * @var string
	 */
	protected $group_name = 'block_wrapper_attributes';

	/**
	 * Functions this sniff is looking for.
	 *
	 * @var array<string, bool>
	 */
	protected $target_functions = [
		'get_block_wrapper_attributes' => true,
	];

	/**
	 * Functions whose array arguments are merged into the result, and are checked as if passed directly.
	 *
	 * @var array<string>
	 */
	private const PASSTHROUGH_FUNCTIONS = [
		'array_merge',
		'array_replace',
	];

	/**
	 * Maximum depth of nested expressions to follow.
	 *
	 * @var int
	 */
	private const MAX_DEPTH = 10;

	/**
	 * Errors already reported in the current file, keyed by "position:code".
	 *
	 * @var array<string, bool>
	 */
	private array $reported = [];

	/**
	 * Path of the file currently being processed.
	 *
	 * @var string
	 */
	private string $current_file = '';

	/**
	 * Position of the get_block_wrapper_attributes() call being checked.
	 *
	 * @var int
	 */
	private int $call_ptr = 0;

	/**
	 * Process the parameters of a matched function.
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$file_path = $this->phpcsFile->getFilename();
		if ( $this->current_file !== $file_path ) {
			$this->current_file = $file_path;
			$this->reported     = [];
		}

		$this->call_ptr = $stackPtr;

		$param = PassedParameters::getParameterFromStack( $parameters, 1, 'extra_attributes' );
		if ( false === $param ) {
			return;
		}

		$this->check_array_expression( $param['start'], $param['end'], 0 );
	}

	/**
	 * Checks an expression that evaluates to an array of attributes.
	 *
	 * @param int $start First token of the expression.
	 * @param int $end   Last token of the expression.
	 * @param int $depth Current recursion depth.
	 */
	private function check_array_expression( int $start, int $end, int $depth ): void {
		$range = $this->trim_range( $start, $end );
		if ( null === $range ) {
			return;
		}
		[ $start, $end ] = $range;

		if ( $depth > self::MAX_DEPTH ) {
			$this->report_unverifiable( $start, 'the expression is nested too deeply to follow' );
			return;
		}

		$code = $this->tokens[ $start ]['code'];

		// Wrapping parentheses.
		if ( T_OPEN_PARENTHESIS === $code
			&& ! isset( $this->tokens[ $start ]['parenthesis_owner'] )
			&& $this->tokens[ $start ]['parenthesis_closer'] === $end
		) {
			$this->check_array_expression( $start + 1, $end - 1, $depth + 1 );
			return;
		}

		// Array literals.
		$is_short_array = T_OPEN_SHORT_ARRAY === $code && $this->tokens[ $start ]['bracket_closer'] === $end;
		$is_long_array  = T_ARRAY === $code
			&& isset( $this->tokens[ $start ]['parenthesis_closer'] )
			&& $this->tokens[ $start ]['parenthesis_closer'] === $end;
		if ( $is_short_array || $is_long_array ) {
			$this->check_array_literal( $start, $depth );
			return;
		}

		// Function calls whose arguments are merged into the result.
		$call = $this->get_function_call( $start, $end );
		if ( null !== $call && in_array( $call['name'], self::PASSTHROUGH_FUNCTIONS, true ) ) {
			foreach ( PassedParameters::getParameters( $this->phpcsFile, $call['ptr'] ) as $param ) {
				$this->check_array_expression( $param['start'], $param['end'], $depth + 1 );
			}
			return;
		}

		$this->report_unverifiable(
			$start,
			sprintf( '"%s" is not an array literal', $this->get_content( $start, $end ) )
		);
	}

	/**
	 * Checks each key in an array literal.
	 *
	 * @param int $open  The array opener (T_OPEN_SHORT_ARRAY or T_ARRAY).
	 * @param int $depth Current recursion depth.
	 */
	private function check_array_literal( int $open, int $depth ): void {
		foreach ( PassedParameters::getParameters( $this->phpcsFile, $open ) as $item ) {
			$range = $this->trim_range( $item['start'], $item['end'] );
			if ( null === $range ) {
				continue;
			}
			[ $start, $end ] = $range;

			// Spread: the unpacked array's keys are merged in.
			if ( T_ELLIPSIS === $this->tokens[ $start ]['code'] ) {
				$this->check_array_expression( $start + 1, $end, $depth + 1 );
				continue;
			}

			$arrow = Arrays::getDoubleArrowPtr( $this->phpcsFile, $start, $end );
			if ( false === $arrow ) {
				// No key: PHP assigns an integer key.
				continue;
			}

			$this->check_key( $start, $arrow - 1 );
		}
	}

	/**
	 * Reports a key expression that is not provably safe.
	 *
	 * @param int $start First token of the key expression.
	 * @param int $end   Last token of the key expression.
	 */
	private function check_key( int $start, int $end ): void {
		$range = $this->trim_range( $start, $end );
		if ( null === $range || $this->is_safe_key( $range[0], $range[1] ) ) {
			return;
		}

		$this->report(
			'Keys passed to get_block_wrapper_attributes() (line %s) become HTML attribute names and are not escaped. Use a string literal, or sanitize_key() after a literal prefix containing a hyphen, such as \'data-\' . sanitize_key( $key ); sanitize_key() alone still allows names like onclick. Found %s.',
			$range[0],
			'UnescapedDynamicKey',
			[ $this->tokens[ $this->call_ptr ]['line'], $this->get_content( $range[0], $range[1] ) ]
		);
	}

	/**
	 * Whether a key expression is provably safe to use as an attribute name.
	 *
	 * Safe keys are literals, or concatenations of literals and sanitize_key()
	 * calls. A key containing sanitize_key() must start with a string literal
	 * containing a hyphen: sanitize_key() allows any lowercase name, including
	 * event handlers like `onclick`, and no event handler contains a hyphen.
	 * Every literal in it must also be limited to the characters sanitize_key()
	 * allows, so a literal like `' on'` can't start a second attribute.
	 *
	 * @param int $start First non-empty token of the key expression.
	 * @param int $end   Last non-empty token of the key expression.
	 * @return bool
	 */
	private function is_safe_key( int $start, int $end ): bool {
		$operands = $this->get_concat_operands( $start, $end );
		if ( null === $operands ) {
			return false;
		}

		$has_sanitized = false;
		$has_unsafe    = false;

		foreach ( $operands as [ $operand_start, $operand_end ] ) {
			if ( $operand_start === $operand_end
				&& in_array( $this->tokens[ $operand_start ]['code'], [ T_CONSTANT_ENCAPSED_STRING, T_LNUMBER ], true )
			) {
				if ( ! preg_match( '/^([\'"]?)[A-Za-z0-9_-]*\1$/', $this->tokens[ $operand_start ]['content'] ) ) {
					$has_unsafe = true;
				}
				continue;
			}

			$call = $this->get_function_call( $operand_start, $operand_end );
			if ( null === $call || 'sanitize_key' !== $call['name'] ) {
				return false;
			}

			$has_sanitized = true;
		}

		if ( ! $has_sanitized ) {
			return true;
		}

		if ( $has_unsafe ) {
			return false;
		}

		$prefix = $operands[0][0];

		return $operands[0][1] === $prefix
			&& T_CONSTANT_ENCAPSED_STRING === $this->tokens[ $prefix ]['code']
			&& false !== strpos( $this->tokens[ $prefix ]['content'], '-' );
	}

	/**
	 * Splits a concatenation into its operands, unwrapping parentheses.
	 *
	 * @param int $start First token of the expression.
	 * @param int $end   Last token of the expression.
	 * @return array<array{int, int}>|null First and last non-empty token of each operand, or null if one is empty.
	 */
	private function get_concat_operands( int $start, int $end ): ?array {
		$range = $this->trim_range( $start, $end );
		if ( null === $range ) {
			return null;
		}
		[ $start, $end ] = $range;

		if ( T_OPEN_PARENTHESIS === $this->tokens[ $start ]['code']
			&& ! isset( $this->tokens[ $start ]['parenthesis_owner'] )
			&& $this->tokens[ $start ]['parenthesis_closer'] === $end
		) {
			return $this->get_concat_operands( $start + 1, $end - 1 );
		}

		$concat = $this->find_top_level( $start, $end, [ T_STRING_CONCAT ] );
		if ( null === $concat ) {
			return [ [ $start, $end ] ];
		}

		$left  = $this->get_concat_operands( $start, $concat - 1 );
		$right = $this->get_concat_operands( $concat + 1, $end );

		return null === $left || null === $right ? null : array_merge( $left, $right );
	}

	/**
	 * Returns the details of a function call that spans exactly the given range.
	 *
	 * Matches `name( ... )` and `\name( ... )`, but not method calls or namespaced calls.
	 *
	 * @param int $start First non-empty token.
	 * @param int $end   Last non-empty token.
	 * @return array{name: string, ptr: int}|null
	 */
	private function get_function_call( int $start, int $end ): ?array {
		$name_ptr = $start;
		if ( T_NS_SEPARATOR === $this->tokens[ $name_ptr ]['code'] ) {
			$name_ptr = $this->phpcsFile->findNext( Tokens::$emptyTokens, $name_ptr + 1, $end, true );
		}

		if ( false === $name_ptr || T_STRING !== $this->tokens[ $name_ptr ]['code'] ) {
			return null;
		}

		$open = $this->phpcsFile->findNext( Tokens::$emptyTokens, $name_ptr + 1, $end, true );
		if ( false === $open
			|| T_OPEN_PARENTHESIS !== $this->tokens[ $open ]['code']
			|| $this->tokens[ $open ]['parenthesis_closer'] !== $end
		) {
			return null;
		}

		return [
			'name' => strtolower( $this->tokens[ $name_ptr ]['content'] ),
			'ptr'  => $name_ptr,
		];
	}

	/**
	 * Finds the first token of the given types at the top nesting level of a range.
	 *
	 * @param int               $start First token.
	 * @param int               $end   Last token.
	 * @param array<int|string> $codes Token codes to find.
	 * @return int|null
	 */
	private function find_top_level( int $start, int $end, array $codes ): ?int {
		for ( $i = $start; $i <= $end; $i++ ) {
			$closer = $this->get_closer( $i );
			if ( null !== $closer ) {
				$i = $closer;
				continue;
			}

			if ( in_array( $this->tokens[ $i ]['code'], $codes, true ) ) {
				return $i;
			}
		}

		return null;
	}

	/**
	 * Returns the matching closer if the token opens a nested group.
	 *
	 * @param int $ptr The token.
	 * @return int|null
	 */
	private function get_closer( int $ptr ): ?int {
		$token = $this->tokens[ $ptr ];

		if ( T_OPEN_PARENTHESIS === $token['code'] && isset( $token['parenthesis_closer'] ) ) {
			return $token['parenthesis_closer'];
		}

		if ( in_array( $token['code'], [ T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET, T_OPEN_CURLY_BRACKET ], true ) && isset( $token['bracket_closer'] ) ) {
			return $token['bracket_closer'];
		}

		return null;
	}

	/**
	 * Narrows a range to its first and last non-empty tokens.
	 *
	 * @param int $start First token.
	 * @param int $end   Last token.
	 * @return array{int, int}|null
	 */
	private function trim_range( int $start, int $end ): ?array {
		if ( $start > $end ) {
			return null;
		}

		$first = $this->phpcsFile->findNext( Tokens::$emptyTokens, $start, $end + 1, true );
		if ( false === $first ) {
			return null;
		}

		$last = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $end, $first - 1, true );

		return [ $first, false === $last ? $first : $last ];
	}

	/**
	 * Returns the source of a range of tokens, without comments.
	 *
	 * @param int $start First token.
	 * @param int $end   Last token.
	 * @return string
	 */
	private function get_content( int $start, int $end ): string {
		return trim( preg_replace( '/\s+/', ' ', $this->phpcsFile->getTokensAsString( $start, $end - $start + 1 ) ) );
	}

	/**
	 * Reports keys that cannot be verified.
	 *
	 * @param int    $ptr    Position to report at.
	 * @param string $reason Why the keys cannot be verified.
	 */
	private function report_unverifiable( int $ptr, string $reason ): void {
		$this->report(
			'Keys passed to get_block_wrapper_attributes() (line %s) become HTML attribute names and are not escaped, and these keys cannot be verified: %s. Pass an array literal using string literal keys, or sanitize_key() after a literal prefix such as \'data-\'.',
			$ptr,
			'UnverifiableKeys',
			[ $this->tokens[ $this->call_ptr ]['line'], $reason ]
		);
	}

	/**
	 * Adds an error once per position and code.
	 *
	 * @param string             $message The error message.
	 * @param int                $ptr     Position to report at.
	 * @param string             $code    The error code.
	 * @param array<int, string> $data    Replacements for the message.
	 */
	private function report( string $message, int $ptr, string $code, array $data ): void {
		$key = $ptr . ':' . $code;
		if ( isset( $this->reported[ $key ] ) ) {
			return;
		}
		$this->reported[ $key ] = true;

		$this->phpcsFile->addError( $message, $ptr, $code, $data );
	}
}
