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
 * such as `'data-' . sanitize_key( $key )`. A bare sanitize_key() call is not
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
 *  - Ternaries, short ternaries and null coalescing, checking each branch.
 *  - Variables, traced through every write in the same function scope (or file
 *    scope, outside of functions) that appears before the call. When the call
 *    is inside a loop, writes later in the loop body also count. Writes with
 *    `=`, `+=` and `??=` are followed; element writes have their key checked.
 *
 * A traced variable is unverifiable when it is a parameter (including an arrow
 * function parameter), closure `use` variable, global, static, foreach
 * variable, destructuring target, reference (including a by-reference closure
 * capture), is never assigned, is changed by any other operator, or is passed
 * to a function that might modify it before the call. It is also unverifiable
 * when the scope contains extract(), include/require, eval(), a variable
 * variable (`$$name`) or a write to $GLOBALS before the call, since those can
 * change it without naming it. A function, including a namespaced call such as
 * `Project\array_merge()`, is only allowed if listed in READ_ONLY_FUNCTIONS,
 * since its definition isn't loaded. Passing an element, such as
 * `foo( $attrs[ $key ] )`, is checked like a write to that key, since a
 * by-reference parameter creates it.
 *
 * At file scope, a variable may already be set by the code that includes the
 * file (block render templates receive `$attributes`, `$content` and `$block`),
 * so it also needs an assignment that always runs before the call: one in the
 * same block as the call or an enclosing one, not inside a condition.
 *
 * Where errors are reported (this is also where a phpcs:ignore must go):
 *  - Inline keys: on the key.
 *  - Traced element writes, including elements passed to a function: on the
 *    key, e.g. `$attrs[ $key ] = 1;`.
 *  - Traced opaque writes or function calls: on that use of the variable.
 *  - extract(), include/require, eval(), `$$name` and $GLOBALS: on that token.
 *  - Traced untraceable assignments, e.g. `$attrs = helper();`: on the value.
 *  - Parameters, unassigned variables and untraceable expressions passed
 *    directly: on the argument passed to get_block_wrapper_attributes().
 * Every message includes the line of the get_block_wrapper_attributes() call.
 *
 * Known limitations:
 *  - Arrow functions capture the enclosing scope by value, so apart from their
 *    parameters, a call inside one is traced through the enclosing function.
 *  - An unqualified call such as `array_merge( $attrs )` is assumed to be the
 *    global function, even in a namespace that defines its own.
 *  - Some safe shapes are reported as UnverifiableKeys rather than followed:
 *    binary `+` unions (`$a + $b`), chained assignments (`$a = $b = [...]`),
 *    closure `use` variables, and array_map()/array_filter() results.
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
	 * Functions a traced variable can be passed to without it becoming unverifiable.
	 *
	 * @var array<string>
	 */
	private const READ_ONLY_FUNCTIONS = [
		'array_merge',
		'array_replace',
		'get_block_wrapper_attributes',
		'sanitize_key',
	];

	/**
	 * Tokens that start a loop.
	 *
	 * @var array<int|string>
	 */
	private const LOOP_TOKENS = [ T_FOR, T_FOREACH, T_WHILE, T_DO ];

	/**
	 * Maximum depth of nested expressions and variable chains to follow.
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
	 * Variables already traced for the current call, keyed by "name@position".
	 *
	 * @var array<string, bool>
	 */
	private array $traced = [];

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
		$this->traced   = [];

		$param = PassedParameters::getParameterFromStack( $parameters, 1, 'extra_attributes' );
		if ( false === $param ) {
			return;
		}

		$this->check_array_expression( $param['start'], $param['end'], $stackPtr, 0 );
	}

	/**
	 * Checks an expression that evaluates to an array of attributes.
	 *
	 * @param int $start   First token of the expression.
	 * @param int $end     Last token of the expression.
	 * @param int $ref_ptr Position the expression is evaluated at, for variable tracing.
	 * @param int $depth   Current recursion depth.
	 */
	private function check_array_expression( int $start, int $end, int $ref_ptr, int $depth ): void {
		$range = $this->trim_range( $start, $end );
		if ( null === $range ) {
			return;
		}
		[ $start, $end ] = $range;

		if ( $depth > self::MAX_DEPTH ) {
			$this->report_unverifiable( $start, 'the expression is nested too deeply to follow' );
			return;
		}

		// Ternaries: every possible result must be safe.
		$then = $this->find_top_level( $start, $end, [ T_INLINE_THEN ] );
		if ( null !== $then ) {
			$else = $this->find_top_level( $then + 1, $end, [ T_INLINE_ELSE ] );
			if ( null === $else ) {
				$this->report_unverifiable( $start, 'the ternary could not be parsed' );
				return;
			}

			$after_then = $this->phpcsFile->findNext( Tokens::$emptyTokens, $then + 1, null, true );
			if ( $after_then === $else ) {
				// Short ternary: the condition itself is a possible result.
				$this->check_array_expression( $start, $then - 1, $ref_ptr, $depth + 1 );
			} else {
				$this->check_array_expression( $then + 1, $else - 1, $ref_ptr, $depth + 1 );
			}
			$this->check_array_expression( $else + 1, $end, $ref_ptr, $depth + 1 );
			return;
		}

		// Null coalescing: both sides are possible results.
		$coalesce = $this->find_top_level( $start, $end, [ T_COALESCE ] );
		if ( null !== $coalesce ) {
			$this->check_array_expression( $start, $coalesce - 1, $ref_ptr, $depth + 1 );
			$this->check_array_expression( $coalesce + 1, $end, $ref_ptr, $depth + 1 );
			return;
		}

		$code = $this->tokens[ $start ]['code'];

		// Wrapping parentheses.
		if ( T_OPEN_PARENTHESIS === $code
			&& ! isset( $this->tokens[ $start ]['parenthesis_owner'] )
			&& $this->tokens[ $start ]['parenthesis_closer'] === $end
		) {
			$this->check_array_expression( $start + 1, $end - 1, $ref_ptr, $depth + 1 );
			return;
		}

		// Array literals.
		$is_short_array = T_OPEN_SHORT_ARRAY === $code && $this->tokens[ $start ]['bracket_closer'] === $end;
		$is_long_array  = T_ARRAY === $code
			&& isset( $this->tokens[ $start ]['parenthesis_closer'] )
			&& $this->tokens[ $start ]['parenthesis_closer'] === $end;
		if ( $is_short_array || $is_long_array ) {
			$this->check_array_literal( $start, $ref_ptr, $depth );
			return;
		}

		// Variables.
		if ( T_VARIABLE === $code && $start === $end ) {
			$this->trace_variable( $start, $ref_ptr, $depth );
			return;
		}

		// Function calls whose arguments are merged into the result.
		$call = $this->get_function_call( $start, $end );
		if ( null !== $call && in_array( $call['name'], self::PASSTHROUGH_FUNCTIONS, true ) ) {
			foreach ( PassedParameters::getParameters( $this->phpcsFile, $call['ptr'] ) as $param ) {
				$this->check_array_expression( $param['start'], $param['end'], $ref_ptr, $depth + 1 );
			}
			return;
		}

		$this->report_unverifiable(
			$start,
			sprintf( '"%s" is not an array literal or a variable that can be traced', $this->get_content( $start, $end ) )
		);
	}

	/**
	 * Checks each key in an array literal.
	 *
	 * @param int $open    The array opener (T_OPEN_SHORT_ARRAY or T_ARRAY).
	 * @param int $ref_ptr Position the expression is evaluated at, for variable tracing.
	 * @param int $depth   Current recursion depth.
	 */
	private function check_array_literal( int $open, int $ref_ptr, int $depth ): void {
		foreach ( PassedParameters::getParameters( $this->phpcsFile, $open ) as $item ) {
			$range = $this->trim_range( $item['start'], $item['end'] );
			if ( null === $range ) {
				continue;
			}
			[ $start, $end ] = $range;

			// Spread: the unpacked array's keys are merged in.
			if ( T_ELLIPSIS === $this->tokens[ $start ]['code'] ) {
				$this->check_array_expression( $start + 1, $end, $ref_ptr, $depth + 1 );
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

		foreach ( $operands as [ $operand_start, $operand_end ] ) {
			if ( $operand_start === $operand_end
				&& in_array( $this->tokens[ $operand_start ]['code'], [ T_CONSTANT_ENCAPSED_STRING, T_LNUMBER ], true )
			) {
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
	 * Traces every write to a variable that can affect its value at a given position.
	 *
	 * @param int $var_ptr The variable token being traced.
	 * @param int $ref_ptr Position the variable is read at.
	 * @param int $depth   Current recursion depth.
	 */
	private function trace_variable( int $var_ptr, int $ref_ptr, int $depth ): void {
		$name      = $this->tokens[ $var_ptr ]['content'];
		$trace_key = $name . '@' . $ref_ptr;

		if ( isset( $this->traced[ $trace_key ] ) ) {
			return;
		}
		$this->traced[ $trace_key ] = true;

		if ( '$this' === $name ) {
			$this->report_unverifiable( $var_ptr, '$this is an object, not an array' );
			return;
		}

		// Find the enclosing function scope, or the whole file outside of functions.
		$owner       = $this->get_scope_owner( $ref_ptr );
		$scope_start = null === $owner ? 0 : $this->tokens[ $owner ]['scope_opener'] + 1;

		if ( null !== $owner && $this->is_scope_input( $owner, $name ) ) {
			$this->report_unverifiable(
				$var_ptr,
				sprintf( '%s is a parameter or closure "use" variable, so its keys are not known', $name )
			);
			return;
		}

		if ( $this->is_arrow_function_parameter( $scope_start, $ref_ptr, $name ) ) {
			$this->report_unverifiable(
				$var_ptr,
				sprintf( '%s is an arrow function parameter, so its keys are not known', $name )
			);
			return;
		}

		// Writes later in an enclosing loop body run before the next iteration.
		$limit = $ref_ptr;
		foreach ( $this->tokens[ $ref_ptr ]['conditions'] as $condition_ptr => $condition_code ) {
			if ( ( null === $owner || $condition_ptr > $owner )
				&& in_array( $condition_code, self::LOOP_TOKENS, true )
				&& isset( $this->tokens[ $condition_ptr ]['scope_closer'] )
			) {
				$limit = max( $limit, $this->tokens[ $condition_ptr ]['scope_closer'] );
			}
		}

		$has_base             = false;
		$has_conditional_base = false;
		$has_opaque           = false;

		for ( $i = $scope_start; $i <= $limit; $i++ ) {
			$code = $this->tokens[ $i ]['code'];

			// Skip nested functions, closures and classes that don't contain the reference point.
			if ( ( in_array( $code, [ T_FUNCTION, T_CLOSURE, T_FN ], true ) || isset( Tokens::$ooScopeTokens[ $code ] ) )
				&& isset( $this->tokens[ $i ]['scope_closer'] )
				&& $i !== $owner
				&& ! ( $i < $ref_ptr && $this->tokens[ $i ]['scope_closer'] >= $ref_ptr )
			) {
				if ( T_CLOSURE === $code ) {
					$by_ref = $this->find_closure_use_by_reference( $i, $name );
					if ( null !== $by_ref ) {
						$this->report_unverifiable(
							$by_ref,
							sprintf( '%s is captured by reference in a closure before it is passed to get_block_wrapper_attributes() on line %s', $name, $this->tokens[ $this->call_ptr ]['line'] )
						);
					}
				}

				$i = $this->tokens[ $i ]['scope_closer'];
				continue;
			}

			$hidden_write = $this->get_hidden_write( $i );
			if ( null !== $hidden_write ) {
				$has_opaque = true;
				$this->report_unverifiable(
					$i,
					sprintf( '%s can change %s without naming it', $hidden_write, $name )
				);
				continue;
			}

			// When tracing the value of a write, the write itself isn't part of it,
			// so `$attrs = $attrs ?? []` doesn't vouch for its own right-hand side.
			if ( T_VARIABLE !== $code || $name !== $this->tokens[ $i ]['content'] || $i === $var_ptr || $i === $ref_ptr ) {
				continue;
			}

			$usage = $this->classify_usage( $i );

			switch ( $usage['type'] ) {
				case 'assign':
					// At file scope the variable may already be set, so only an assignment that always runs counts.
					if ( null !== $owner || $this->always_runs_before( $i, $ref_ptr ) ) {
						$has_base = true;
					} else {
						$has_conditional_base = true;
					}
					$this->check_array_expression( $usage['start'], $usage['end'], $i, $depth + 1 );
					break;

				case 'merge':
					$this->check_array_expression( $usage['start'], $usage['end'], $i, $depth + 1 );
					break;

				case 'element':
					if ( null !== $usage['start'] ) {
						$this->check_key( $usage['start'], $usage['end'] );
					}
					break;

				case 'opaque':
					$has_opaque = true;
					$this->report_unverifiable( $i, $usage['reason'] );
					break;
			}
		}

		if ( $has_base || $has_opaque ) {
			return;
		}

		if ( $has_conditional_base ) {
			$this->report_unverifiable(
				$var_ptr,
				sprintf( '%s is only assigned conditionally at file scope, where it may already be set by the code that includes the file (block render templates receive $attributes, $content and $block)', $name )
			);
			return;
		}

		$this->report_unverifiable(
			$var_ptr,
			sprintf( 'no assignment to %s was found in the same scope before it is used', $name )
		);
	}

	/**
	 * Whether a variable is a parameter of an arrow function containing a position.
	 *
	 * Arrow functions are not added to a token's conditions, so they are found by scanning the scope.
	 *
	 * @param int    $scope_start First token of the enclosing function or file scope.
	 * @param int    $ptr         The position.
	 * @param string $name        The variable name, including the $.
	 * @return bool
	 */
	private function is_arrow_function_parameter( int $scope_start, int $ptr, string $name ): bool {
		for ( $i = $scope_start; $i < $ptr; $i++ ) {
			if ( T_FN !== $this->tokens[ $i ]['code']
				|| ! isset( $this->tokens[ $i ]['scope_opener'], $this->tokens[ $i ]['scope_closer'] )
				|| $this->tokens[ $i ]['scope_closer'] < $ptr
			) {
				continue;
			}

			foreach ( $this->phpcsFile->getMethodParameters( $i ) as $param ) {
				if ( $name === $param['name'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Whether an assignment always runs before a position is reached.
	 *
	 * True when the assignment comes first, is the start of its statement, and is
	 * in the same block as the position or an enclosing one.
	 *
	 * @param int $ptr     The assigned T_VARIABLE token.
	 * @param int $ref_ptr The position.
	 * @return bool
	 */
	private function always_runs_before( int $ptr, int $ref_ptr ): bool {
		if ( $ptr >= $ref_ptr || $this->phpcsFile->findStartOfStatement( $ptr ) !== $ptr ) {
			return false;
		}

		if ( array_diff_key( $this->tokens[ $ptr ]['conditions'], $this->tokens[ $ref_ptr ]['conditions'] ) ) {
			return false;
		}

		// Control structures without braces don't add conditions.
		$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $ptr - 1, null, true );
		if ( false === $prev ) {
			return true;
		}

		if ( in_array( $this->tokens[ $prev ]['code'], [ T_ELSE, T_DO ], true ) ) {
			return false;
		}

		return ! ( T_CLOSE_PARENTHESIS === $this->tokens[ $prev ]['code']
			&& isset( $this->tokens[ $prev ]['parenthesis_owner'] )
			&& in_array( $this->tokens[ $this->tokens[ $prev ]['parenthesis_owner'] ]['code'], [ T_IF, T_ELSEIF, T_WHILE, T_FOR, T_FOREACH ], true ) );
	}

	/**
	 * Detects constructs that can change any local variable without naming it.
	 *
	 * @param int $ptr The token.
	 * @return string|null A description of the construct, or null if it is not one.
	 */
	private function get_hidden_write( int $ptr ): ?string {
		$code = $this->tokens[ $ptr ]['code'];

		if ( in_array( $code, [ T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE ], true ) ) {
			return strtolower( $this->tokens[ $ptr ]['content'] );
		}

		if ( T_EVAL === $code ) {
			return 'eval()';
		}

		if ( T_DOLLAR === $code ) {
			return 'a variable variable';
		}

		if ( T_VARIABLE === $code && '$GLOBALS' === $this->tokens[ $ptr ]['content'] ) {
			return 'read' === $this->classify_usage( $ptr )['type'] ? null : 'a write to $GLOBALS';
		}

		if ( T_STRING === $code && 'extract' === strtolower( $this->tokens[ $ptr ]['content'] ) ) {
			$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $ptr + 1, null, true );
			$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $ptr - 1, null, true );

			if ( false === $next || T_OPEN_PARENTHESIS !== $this->tokens[ $next ]['code'] ) {
				return null;
			}

			// Method calls, static calls, instantiation and declarations aren't the global function.
			if ( false !== $prev && in_array( $this->tokens[ $prev ]['code'], [ T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW, T_FUNCTION ], true ) ) {
				return null;
			}

			// Namespaced calls such as Foo\extract() aren't the global function either.
			if ( false !== $prev && T_NS_SEPARATOR === $this->tokens[ $prev ]['code'] ) {
				$before_separator = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $prev - 1, null, true );
				if ( false !== $before_separator && T_STRING === $this->tokens[ $before_separator ]['code'] ) {
					return null;
				}
			}

			return 'extract()';
		}

		return null;
	}

	/**
	 * Determines how a variable is used at a given position.
	 *
	 * @param int $ptr The T_VARIABLE token.
	 * @return array{type: string, start?: int|null, end?: int|null, reason?: string}
	 */
	private function classify_usage( int $ptr ): array {
		$name = $this->tokens[ $ptr ]['content'];
		$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $ptr - 1, null, true );
		$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $ptr + 1, null, true );

		$prev_code = false === $prev ? null : $this->tokens[ $prev ]['code'];
		$next_code = false === $next ? null : $this->tokens[ $next ]['code'];

		// Static property access such as self::$name.
		if ( in_array( $prev_code, [ T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR ], true ) ) {
			return [ 'type' => 'read' ];
		}

		if ( T_BITWISE_AND === $prev_code && $this->phpcsFile->isReference( $prev ) ) {
			return $this->opaque( sprintf( '%s is used by reference', $name ) );
		}

		$statement_start = $this->phpcsFile->findStartOfStatement( $ptr );
		if ( in_array( $this->tokens[ $statement_start ]['code'], [ T_GLOBAL, T_STATIC ], true ) ) {
			return $this->opaque( sprintf( '%s is declared global or static', $name ) );
		}

		// Destructuring: [ $name ] = ... or list( $name ) = ...
		if ( $this->is_short_list_target( $ptr ) ) {
			return $this->opaque( sprintf( '%s is assigned by destructuring', $name ) );
		}

		if ( ! empty( $this->tokens[ $ptr ]['nested_parenthesis'] ) ) {
			$opener = array_key_last( $this->tokens[ $ptr ]['nested_parenthesis'] );
			$owner  = $this->tokens[ $opener ]['parenthesis_owner'] ?? null;

			if ( null !== $owner && T_LIST === $this->tokens[ $owner ]['code'] ) {
				return $this->opaque( sprintf( '%s is assigned by destructuring', $name ) );
			}

			if ( null !== $owner && T_FOREACH === $this->tokens[ $owner ]['code']
				&& false !== $this->phpcsFile->findPrevious( T_AS, $ptr - 1, $opener )
			) {
				return $this->opaque( sprintf( '%s is assigned by a foreach loop', $name ) );
			}
		}

		// Whole-variable writes.
		if ( null !== $next_code && isset( Tokens::$assignmentTokens[ $next_code ] ) ) {
			$end = $this->find_end_of_expression( $next + 1 );

			if ( T_EQUAL === $next_code ) {
				return [
					'type'  => 'assign',
					'start' => $next + 1,
					'end'   => $end,
				];
			}

			if ( in_array( $next_code, [ T_PLUS_EQUAL, T_COALESCE_EQUAL ], true ) ) {
				return [
					'type'  => 'merge',
					'start' => $next + 1,
					'end'   => $end,
				];
			}

			return $this->opaque( sprintf( '%s is modified with "%s"', $name, $this->tokens[ $next ]['content'] ) );
		}

		if ( in_array( $next_code, [ T_INC, T_DEC ], true ) || in_array( $prev_code, [ T_INC, T_DEC ], true ) ) {
			return $this->opaque( sprintf( '%s is incremented or decremented', $name ) );
		}

		// Element writes: $name[ key ] = ..., $name[] = ...
		if ( T_OPEN_SQUARE_BRACKET === $next_code && isset( $this->tokens[ $next ]['bracket_closer'] ) ) {
			$closer = $this->tokens[ $next ]['bracket_closer'];
			$after  = $this->phpcsFile->findNext( Tokens::$emptyTokens, $closer + 1, null, true );

			// Nested dimensions only change the value of the first key.
			while ( false !== $after
				&& T_OPEN_SQUARE_BRACKET === $this->tokens[ $after ]['code']
				&& isset( $this->tokens[ $after ]['bracket_closer'] )
			) {
				$after = $this->phpcsFile->findNext( Tokens::$emptyTokens, $this->tokens[ $after ]['bracket_closer'] + 1, null, true );
			}

			if ( false !== $after
				&& ( isset( Tokens::$assignmentTokens[ $this->tokens[ $after ]['code'] ] ) || in_array( $this->tokens[ $after ]['code'], [ T_INC, T_DEC ], true ) )
			) {
				$key = $this->trim_range( $next + 1, $closer - 1 );

				return [
					'type'  => 'element',
					'start' => null === $key ? null : $key[0],
					'end'   => null === $key ? null : $key[1],
				];
			}

			// Passing an element by reference creates it, as `$name[ key ] = null` would.
			$last_closer = false === $after
				? $closer
				: $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $after - 1, null, true );
			$callee      = $this->get_callee_if_direct_argument( $ptr, $last_closer );
			if ( null !== $callee && ! $this->is_read_only_argument( $callee['name'] ) ) {
				$key = $this->trim_range( $next + 1, $closer - 1 );

				return [
					'type'  => 'element',
					'start' => null === $key ? null : $key[0],
					'end'   => null === $key ? null : $key[1],
				];
			}

			return [ 'type' => 'read' ];
		}

		$callee = $this->get_callee_if_direct_argument( $ptr, $ptr );
		if ( null !== $callee && ! $this->is_read_only_argument( $callee['name'] ) ) {
			return $this->opaque(
				sprintf( '%s is passed to %s, which could modify it by reference', $name, $callee['label'] )
			);
		}

		return [ 'type' => 'read' ];
	}

	/**
	 * If an expression is passed directly as an argument to a function or method call, returns the callee.
	 *
	 * Grouping parentheses are looked through, since `foo( ( $attrs ) )` still
	 * passes `$attrs` by reference.
	 *
	 * @param int $start First token of the expression.
	 * @param int $end   Last token of the expression.
	 * @return array{name: string, label: string, param: int|string}|null Lowercase global function name
	 *                                                                    (empty for methods, namespaced and
	 *                                                                    dynamic calls), a description for
	 *                                                                    messages, and parameter position or
	 *                                                                    name, or null if not an argument.
	 */
	private function get_callee_if_direct_argument( int $start, int $end ): ?array {
		if ( empty( $this->tokens[ $start ]['nested_parenthesis'] ) ) {
			return null;
		}

		$callable_tokens = [
			T_STRING,
			T_VARIABLE,
			T_SELF,
			T_STATIC,
			T_PARENT,
			T_CLOSE_PARENTHESIS,
			T_CLOSE_SQUARE_BRACKET,
			T_CLOSE_CURLY_BRACKET,
		];

		$name_ptr = null;

		foreach ( array_reverse( $this->tokens[ $start ]['nested_parenthesis'], true ) as $opener => $closer ) {
			if ( isset( $this->tokens[ $opener ]['parenthesis_owner'] ) ) {
				// Control structures, array(), function declarations and the like.
				return null;
			}

			$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $opener - 1, null, true );
			if ( false !== $prev && in_array( $this->tokens[ $prev ]['code'], $callable_tokens, true ) ) {
				$name_ptr = $prev;
				break;
			}

			// Grouping parentheses around exactly the expression: look through them.
			if ( [ $start, $end ] !== $this->trim_range( $opener + 1, $closer - 1 ) ) {
				return null;
			}

			[ $start, $end ] = [ $opener, $closer ];
		}

		if ( null === $name_ptr ) {
			return null;
		}

		$param_key = null;
		foreach ( PassedParameters::getParameters( $this->phpcsFile, $name_ptr ) as $key => $param ) {
			if ( [ $start, $end ] === $this->trim_range( $param['start'], $param['end'] ) ) {
				$param_key = $key;
				break;
			}
		}

		if ( null === $param_key ) {
			return null;
		}

		$name  = '';
		$label = 'a function or method';

		if ( T_STRING === $this->tokens[ $name_ptr ]['code'] ) {
			$before_name = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $name_ptr - 1, null, true );
			$before_code = false === $before_name ? null : $this->tokens[ $before_name ]['code'];

			if ( T_NS_SEPARATOR === $before_code ) {
				// `\count()` is the global function; `Project\count()` and `namespace\count()` are not.
				$qualifier = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $before_name - 1, null, true );
				if ( false !== $qualifier && in_array( $this->tokens[ $qualifier ]['code'], [ T_STRING, T_NAMESPACE ], true ) ) {
					$label = 'a namespaced function';
				} else {
					$name = strtolower( $this->tokens[ $name_ptr ]['content'] );
				}
			} elseif ( ! in_array( $before_code, [ T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW ], true ) ) {
				$name = strtolower( $this->tokens[ $name_ptr ]['content'] );
			}
		}

		return [
			'name'  => $name,
			'label' => '' === $name ? $label : $name . '()',
			'param' => $param_key,
		];
	}

	/**
	 * Whether passing a variable to a function argument cannot modify it.
	 *
	 * Only functions listed in READ_ONLY_FUNCTIONS are allowed, since the
	 * definitions of other functions are not available.
	 *
	 * @param string $name Lowercase function name, or an empty string for methods and dynamic calls.
	 * @return bool
	 */
	private function is_read_only_argument( string $name ): bool {
		return in_array( $name, self::READ_ONLY_FUNCTIONS, true );
	}

	/**
	 * Whether a variable is a target of short list destructuring, such as `[ $a ] = ...`.
	 *
	 * @param int $ptr The T_VARIABLE token.
	 * @return bool
	 */
	private function is_short_list_target( int $ptr ): bool {
		for ( $i = $ptr - 1; $i >= 0; $i-- ) {
			$code = $this->tokens[ $i ]['code'];

			if ( in_array( $code, [ T_SEMICOLON, T_OPEN_TAG, T_OPEN_CURLY_BRACKET, T_CLOSE_CURLY_BRACKET ], true ) ) {
				return false;
			}

			if ( in_array( $code, [ T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET ], true )
				&& isset( $this->tokens[ $i ]['bracket_closer'] )
				&& $this->tokens[ $i ]['bracket_closer'] > $ptr
			) {
				$after = $this->phpcsFile->findNext( Tokens::$emptyTokens, $this->tokens[ $i ]['bracket_closer'] + 1, null, true );
				if ( false !== $after && T_EQUAL === $this->tokens[ $after ]['code'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Finds a by-reference capture of a variable in a closure's use clause.
	 *
	 * @param int    $closure The T_CLOSURE token.
	 * @param string $name    The variable name, including the $.
	 * @return int|null Position of the captured variable, or null if not captured by reference.
	 */
	private function find_closure_use_by_reference( int $closure, string $name ): ?int {
		if ( ! isset( $this->tokens[ $closure ]['parenthesis_closer'], $this->tokens[ $closure ]['scope_opener'] ) ) {
			return null;
		}

		$use = $this->phpcsFile->findNext( T_USE, $this->tokens[ $closure ]['parenthesis_closer'] + 1, $this->tokens[ $closure ]['scope_opener'] );
		if ( false === $use ) {
			return null;
		}

		for ( $i = $use + 1; $i < $this->tokens[ $closure ]['scope_opener']; $i++ ) {
			if ( T_VARIABLE !== $this->tokens[ $i ]['code'] || $name !== $this->tokens[ $i ]['content'] ) {
				continue;
			}

			$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $i - 1, null, true );
			if ( false !== $prev && T_BITWISE_AND === $this->tokens[ $prev ]['code'] ) {
				return $i;
			}
		}

		return null;
	}

	/**
	 * Whether a variable is a parameter or closure use variable of a function scope.
	 *
	 * @param int    $owner The T_FUNCTION or T_CLOSURE token.
	 * @param string $name  The variable name, including the $.
	 * @return bool
	 */
	private function is_scope_input( int $owner, string $name ): bool {
		foreach ( $this->phpcsFile->getMethodParameters( $owner ) as $param ) {
			if ( $name === $param['name'] ) {
				return true;
			}
		}

		if ( T_CLOSURE !== $this->tokens[ $owner ]['code'] ) {
			return false;
		}

		$use = $this->phpcsFile->findNext( T_USE, $this->tokens[ $owner ]['parenthesis_closer'] + 1, $this->tokens[ $owner ]['scope_opener'] );
		if ( false === $use ) {
			return false;
		}

		for ( $i = $use + 1; $i < $this->tokens[ $owner ]['scope_opener']; $i++ ) {
			if ( T_VARIABLE === $this->tokens[ $i ]['code'] && $name === $this->tokens[ $i ]['content'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the innermost function or closure containing a position.
	 *
	 * @param int $ptr The position.
	 * @return int|null The T_FUNCTION or T_CLOSURE token, or null at file scope.
	 */
	private function get_scope_owner( int $ptr ): ?int {
		$owner = null;

		foreach ( $this->tokens[ $ptr ]['conditions'] as $condition_ptr => $condition_code ) {
			if ( in_array( $condition_code, [ T_FUNCTION, T_CLOSURE ], true ) ) {
				$owner = $condition_ptr;
			}
		}

		return $owner;
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
	 * Finds the last token of an expression starting at a given position.
	 *
	 * @param int $start First token of the expression.
	 * @return int
	 */
	private function find_end_of_expression( int $start ): int {
		$stop = [ T_SEMICOLON, T_COMMA, T_CLOSE_TAG, T_CLOSE_PARENTHESIS, T_CLOSE_SQUARE_BRACKET, T_CLOSE_SHORT_ARRAY, T_CLOSE_CURLY_BRACKET ];

		for ( $i = $start; $i < $this->phpcsFile->numTokens; $i++ ) {
			$closer = $this->get_closer( $i );
			if ( null !== $closer ) {
				$i = $closer;
				continue;
			}

			if ( in_array( $this->tokens[ $i ]['code'], $stop, true ) ) {
				return $i - 1;
			}
		}

		return $this->phpcsFile->numTokens - 1;
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
	 * Builds an "opaque" usage result.
	 *
	 * @param string $reason Why the variable's keys cannot be known.
	 * @return array{type: string, reason: string}
	 */
	private function opaque( string $reason ): array {
		return [
			'type'   => 'opaque',
			'reason' => $reason,
		];
	}

	/**
	 * Reports keys that cannot be verified.
	 *
	 * @param int    $ptr    Position to report at.
	 * @param string $reason Why the keys cannot be verified.
	 */
	private function report_unverifiable( int $ptr, string $reason ): void {
		$this->report(
			'Keys passed to get_block_wrapper_attributes() (line %s) become HTML attribute names and are not escaped, and these keys cannot be verified: %s. Build the array in the same scope using string literal keys, or sanitize_key() after a literal prefix such as \'data-\'.',
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
