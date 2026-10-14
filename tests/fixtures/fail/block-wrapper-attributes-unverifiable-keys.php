<?php
/**
 * Block wrapper attributes unverifiable keys fail fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

// Function call argument.
echo get_block_wrapper_attributes( array_merge( $defaults, $extra ) );

// Property argument.
echo get_block_wrapper_attributes( $this->attributes );

// Spread in an inline array.
echo get_block_wrapper_attributes( [
	'class' => 'x',
	...$extra,
] );

// Variable never assigned an array in scope.
echo get_block_wrapper_attributes( $undefined_attrs );

// Variable assigned something other than an array literal.
$alley_attrs = alley_get_attrs();
echo get_block_wrapper_attributes( $alley_attrs );

// Compound assignment.
$alley_more  = [ 'class' => 'x' ];
$alley_more += $extra;
echo get_block_wrapper_attributes( $alley_more );

// Foreach variable.
foreach ( $list as $alley_item ) {
	echo get_block_wrapper_attributes( $alley_item );
}

/**
 * Function parameter.
 *
 * @param array $attributes Attributes.
 */
function alley_render_block_param( $attributes ) {
	$attributes['class'] = 'x';
	return get_block_wrapper_attributes( $attributes );
}

// Passed to a function that writes to it by reference.
$alley_parsed = [];
parse_str( $alley_query, $alley_parsed );
echo get_block_wrapper_attributes( $alley_parsed );

// Passed as a named argument.
$alley_named = [];
alley_fill( into: $alley_named );
echo get_block_wrapper_attributes( $alley_named );

// Short list destructuring.
$alley_short     = [];
[ $alley_short ] = $list;
echo get_block_wrapper_attributes( $alley_short );

// Keyed list() destructuring.
$alley_long                = [];
list( 'k' => $alley_long ) = $list; // phpcs:ignore Universal.Lists.DisallowLongListSyntax.Found
echo get_block_wrapper_attributes( $alley_long );

// Destructured foreach variable.
foreach ( $list as [ $alley_pair ] ) {
	echo get_block_wrapper_attributes( $alley_pair );
}

/**
 * Arrow function parameter shadowing a variable built in the outer scope.
 *
 * @param array $list List.
 */
function alley_render_block_arrow( $list ) {
	$attributes = [ 'class' => 'x' ];
	return array_map( fn( $attributes ) => get_block_wrapper_attributes( $attributes ), $list );
}
