<?php
/**
 * Test for Alley.PHP.BlockWrapperAttributes with attributes it can't trace.
 *
 * @global array $attributes Block attributes passed to the render callback.
 *
 * @package Alley\WP\Coding_Standards
 */

// Not assigned in this scope.
echo get_block_wrapper_attributes( $attributes );

// Expressions that aren't a single array literal.
echo get_block_wrapper_attributes( alley_get_attributes() );
echo get_block_wrapper_attributes( array_merge( [ 'class' => 'a' ], alley_get_attributes() ) );
echo get_block_wrapper_attributes( [ 'class' => 'a' ] + alley_get_attributes() );
echo get_block_wrapper_attributes( [ ...alley_get_attributes() ] );

// Assigned from something other than an array literal.
$alley_from_call = alley_get_attributes();

echo get_block_wrapper_attributes( $alley_from_call );

// Merged with something that can't be traced.
$alley_merged = array_merge( [ 'class' => 'a' ], alley_get_attributes() );

echo get_block_wrapper_attributes( $alley_merged );

// Changed by reference.
$alley_by_ref = [ 'class' => 'a' ];
$alley_ref    = &$alley_by_ref;

echo get_block_wrapper_attributes( $alley_by_ref );

// Set by foreach.
foreach ( alley_get_attribute_sets() as $alley_set ) {
	echo get_block_wrapper_attributes( $alley_set );
}

/**
 * Passes a parameter straight through.
 *
 * @param array $attrs Attributes.
 * @return string
 */
function alley_passthrough( $attrs ) {
	return get_block_wrapper_attributes( $attrs );
}

// Arrow function parameter shadowing an assigned outer variable.
$alley_shadowed = [ 'class' => 'a' ];
$alley_render   = fn ( $alley_shadowed ) => get_block_wrapper_attributes( $alley_shadowed );

/**
 * Rebinds a local to a global later in a `global` list.
 *
 * @return string
 */
function alley_global_list() {
	$attrs = [ 'class' => 'a' ];

	global $post, $attrs; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.VariableRedeclaration

	return get_block_wrapper_attributes( $attrs );
}

// Compound assignment keeps the existing keys of an untraced array.
$alley_compound += [ 'class' => 'a' ];

echo get_block_wrapper_attributes( $alley_compound );

// Element write keeps the existing keys of an untraced array.
$alley_element['class'] = 'card';

echo get_block_wrapper_attributes( $alley_element );

// Destructuring replaces the array.
$alley_short_list = [ 'class' => 'a' ];

[ $alley_short_list ] = alley_get_attribute_sets();

echo get_block_wrapper_attributes( $alley_short_list );

$alley_long_list = [ 'class' => 'a' ];

list( $alley_long_list ) = alley_get_attribute_sets(); // phpcs:ignore Universal.Lists.DisallowLongListSyntax.Found

echo get_block_wrapper_attributes( $alley_long_list );

// Assigned only in a branch that may not run.
if ( alley_has_defaults() ) {
	$alley_branch = [ 'class' => 'a' ];
}

echo get_block_wrapper_attributes( $alley_branch );

// Passed to functions and methods that may take it by reference.
$alley_by_ref_arg = [ 'class' => 'a' ];

parse_str( alley_get_query(), $alley_by_ref_arg );

echo get_block_wrapper_attributes( $alley_by_ref_arg );

$alley_method_arg = [ 'class' => 'a' ];

alley_get_builder()->fill( $alley_method_arg );

echo get_block_wrapper_attributes( $alley_method_arg );

/**
 * Overwrites variables with extract().
 *
 * @param array $args Arguments.
 * @return string
 */
function alley_extract( $args ) {
	$attrs = [ 'class' => 'a' ];

	extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

	return get_block_wrapper_attributes( $attrs );
}

/**
 * Overwrites variables with a variable variable.
 *
 * @param string $name Variable name.
 * @return string
 */
function alley_variable_variable( $name ) {
	$attrs = [ 'class' => 'a' ];
	$$name = alley_get_attributes();

	return get_block_wrapper_attributes( $attrs );
}

/**
 * Overwrites variables from an included file.
 *
 * @return string
 */
function alley_include() {
	$attrs = [ 'class' => 'a' ];

	include __DIR__ . '/partial.php';

	return get_block_wrapper_attributes( $attrs );
}

// Captured by reference in a closure.
$alley_captured = [ 'class' => 'a' ];
$alley_change   = function () use ( &$alley_captured ) {
	$alley_captured = alley_get_attributes();
};

$alley_change();

echo get_block_wrapper_attributes( $alley_captured );

// Uses that aren't a known read or write, like a catch variable.
$alley_caught = [ 'class' => 'a' ];

try {
	alley_get_attributes();
} catch ( Exception $alley_caught ) {
	unset( $alley_caught );
}

echo get_block_wrapper_attributes( $alley_caught );
