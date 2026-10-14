<?php
/**
 * Test for Alley.PHP.BlockWrapperAttributes with traced arrays that are read, copied or shadowed.
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Reads, copies, arrow functions and nested closures.
 */
function alley_block_wrapper_attributes_reads(): void {
	$base = [ 'class' => 'base' ];

	// Reads that don't pass the array to a function.
	if ( isset( $base['class'] ) && ! empty( $base ) && 'base' === $base['class'] ) {
		echo $base['class'] . get_block_wrapper_attributes( $base );
	}
	unset( $base['id'] );

	// Copies are traced through.
	$alias = $base;
	$chain = $alias;
	echo get_block_wrapper_attributes( $chain );

	// Arrow functions capture the enclosing scope by value.
	$render = fn() => get_block_wrapper_attributes( $base );
	echo $render();

	// A nested closure's variable with the same name is a different variable.
	$attrs     = [ 'class' => 'outer' ];
	$unrelated = function () {
		$attrs = alley_get_attrs();
		return $attrs;
	};
	echo get_block_wrapper_attributes( $attrs );
	echo esc_html( count( $unrelated() ) );
}

/**
 * Lookalikes of constructs that can change variables, and reads of $GLOBALS.
 *
 * @param object $object An object.
 */
function alley_block_wrapper_attributes_hidden_write_lookalikes( object $object ): void {
	$attrs = [ 'class' => 'x' ];
	$object->extract( [] );
	Alley_Renderer::extract( [] );
	$post_id = $GLOBALS['post']->ID ?? 0;
	echo get_block_wrapper_attributes( $attrs );
	echo esc_html( $post_id );
}
