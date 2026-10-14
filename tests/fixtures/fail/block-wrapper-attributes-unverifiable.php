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
