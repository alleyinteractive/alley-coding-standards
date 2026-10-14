<?php
/**
 * Test for Alley.PHP.BlockWrapperAttributes with arrays traced at file scope and through calls.
 *
 * @global array    $attributes Block attributes passed to the render callback.
 * @global string   $content    Block content from InnerBlocks passed to the render callback.
 * @global WP_Block $block      Block instance.
 *
 * @package Alley\WP\Coding_Standards
 */

$wrapper_classes = [ 'wp-block-example' ];

if ( ! empty( $attributes['align'] ) ) {
	$wrapper_classes[] = 'align' . $attributes['align'];
}

$wrapper_attributes = [
	'class'   => implode( ' ', $wrapper_classes ),
	'data-id' => $attributes['id'] ?? '',
];

$wrapper_attributes['aria-label'] = $attributes['label'] ?? '';

$wrapper_attributes[ 'data-' . sanitize_key( $attributes['group'] ) . '-' . sanitize_key( $attributes['suffix'] ) ] = 'yes';
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); ?>>
	<?php echo wp_kses_post( $content ); ?>
</div>
<?php

// Assigned in the same loop body as the call, so it always runs first.
foreach ( $attributes['items'] ?? [] as $item ) {
	$item_attributes = [ 'class' => 'item' ];
	echo get_block_wrapper_attributes( $item_attributes );
}

/**
 * Allowed functions, repeated calls, and writes after the call.
 *
 * @param bool $is_wide Whether the block is wide.
 */
function alley_block_wrapper_attributes_calls( bool $is_wide ): void {
	$base  = [ 'class' => 'base' ];
	$extra = [ 'data-extra' => 'yes' ];

	$extra[ 'data-' . sanitize_key( 'Thing' ) ] = 'thing';

	echo get_block_wrapper_attributes( array_merge( $base, $extra, [ 'id' => 'merged' ] ) );
	echo get_block_wrapper_attributes( array_replace( $base, [ 'class' => 'replaced' ] ) );
	echo get_block_wrapper_attributes( $base ?? [ 'class' => 'fallback' ] );
	echo \get_block_wrapper_attributes( $base );

	$merged = array_merge( $base, $extra );
	$merged = \array_merge( $merged, [ 'data-again' => 'yes' ] );
	echo get_block_wrapper_attributes( $merged );

	// Writes after the call (outside of a loop) do not matter.
	$later = [ 'class' => 'later' ];
	echo get_block_wrapper_attributes( $later );
	$later[ $is_wide ] = 'ignored';
}
