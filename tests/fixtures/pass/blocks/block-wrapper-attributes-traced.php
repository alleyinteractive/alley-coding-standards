<?php
/**
 * Test for Alley.PHP.BlockWrapperAttributes with arrays traced through variables in a function.
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Assignments, element writes, merges, ternaries, null coalescing and spreads.
 *
 * @param array<string, string> $attributes Block attributes.
 * @param bool                  $is_wide    Whether the block is wide.
 */
function alley_block_wrapper_attributes_traced( array $attributes, bool $is_wide ): void {
	$wrapper = [
		'class'   => 'wp-block-example',
		'data-id' => $attributes['id'] ?? '',
	];

	$wrapper['aria-label'] = $attributes['label'] ?? '';
	$wrapper['class']     .= ' is-ready';
	$wrapper[]             = 'no-key';

	$wrapper[ 'data-' . sanitize_key( $attributes['dataKey'] ) ] = $attributes['dataValue'];

	echo get_block_wrapper_attributes( $wrapper );

	$merged   = array_merge( [ 'class' => 'base' ], [ 'id' => 'merged' ] );
	$merged  += [ 'data-plus' => 'yes' ];
	$merged ??= [ 'class' => 'fallback' ];
	echo get_block_wrapper_attributes( $merged );

	$wide   = [ 'class' => 'wide' ];
	$narrow = [ 'class' => 'narrow' ];
	echo get_block_wrapper_attributes( $is_wide ? $wide : $narrow );

	$maybe = $is_wide ? [ 'class' => 'wide' ] : [];
	echo get_block_wrapper_attributes( $maybe ?: [ 'class' => 'fallback' ] );

	$extra = [ 'data-extra' => 'yes' ];
	echo get_block_wrapper_attributes(
		[
			'class' => 'spread',
			...$extra,
		]
	);

	// A closure is its own scope.
	$render = function () {
		$inner = [ 'class' => 'inner' ];
		return get_block_wrapper_attributes( $inner );
	};
	echo $render();
}
