<?php
/**
 * Test for Alley.PHP.BlockWrapperAttributes with arrays built inline.
 *
 * @global array $attributes Block attributes passed to the render callback.
 *
 * @package Alley\WP\Coding_Standards
 */

// No arguments, or an empty array.
echo get_block_wrapper_attributes();
echo get_block_wrapper_attributes( [] );

// Fully qualified call with an inline literal and dynamic values.
echo \get_block_wrapper_attributes( [ 'id' => $attributes['anchor'] ] );

// Case-insensitive function name.
echo GET_BLOCK_WRAPPER_ATTRIBUTES( [ 'class' => 'upper' ] );

// Named argument.
echo get_block_wrapper_attributes( extra_attributes: [ 'class' => 'named' ] );

// Integer keys, literal concatenations, and prefixed sanitize_key() keys inline.
echo get_block_wrapper_attributes(
	[
		0                                                  => 'zero',
		'data-' . sanitize_key( $attributes['k'] )         => 'b',
		( 'data-grouped' )                                 => 'c',
		( 'aria-' ) . ( sanitize_key( $attributes['a'] ) ) => 'd',
		'data-' . sanitize_key( $attributes['b'] ) . '-x'  => 'e',
	]
);

// Merging, replacing and spreads of literals.
echo get_block_wrapper_attributes( array_merge( [ 'class' => 'base' ], [ 'id' => 'merged' ] ) );
echo get_block_wrapper_attributes( array_replace( [ 'class' => 'base' ], [ 'class' => 'replaced' ] ) );
echo get_block_wrapper_attributes(
	[
		'class' => 'spread',
		...[ 'data-extra' => 'yes' ],
	]
);

/**
 * Calls with that name that are not the global function.
 *
 * @param object $renderer A renderer object.
 * @param string $key      A dynamic key.
 */
function alley_block_wrapper_attributes_lookalikes( object $renderer, string $key ): void {
	echo esc_attr( $renderer->get_block_wrapper_attributes( [ $key => 1 ] ) );
	echo esc_attr( Alley_Renderer::get_block_wrapper_attributes( [ $key => 1 ] ) );
}
