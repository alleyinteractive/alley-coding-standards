<?php
/**
 * Fail fixture for Alley.PHP.BlockWrapperAttributes inside a loop.
 *
 * The unsafe write happens after the call in source order, but runs before the
 * call on the next iteration.
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Builds wrappers in a loop.
 *
 * @param array<string, string> $items Items to loop over.
 */
function alley_block_wrapper_loop( array $items ): void {
	$attrs = [ 'class' => 'item' ];

	foreach ( $items as $key => $value ) {
		echo get_block_wrapper_attributes( $attrs );

		$attrs[ $key ] = $value;
	}
}
