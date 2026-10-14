<?php
/**
 * Test for Alley.PHP.BlockWrapperAttributes with arrays traced through variables.
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
$wrapper_attributes['class']     .= ' is-ready';
$wrapper_attributes[]             = 'no-key';

$wrapper_attributes[ 'data-' . sanitize_key( $attributes['dataKey'] ) ] = $attributes['dataValue'];

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
 * Merging, replacing, ternaries, null coalescing, spreads and variable chains.
 *
 * @param bool $is_wide Whether the block is wide.
 */
function alley_block_wrapper_attributes_shapes( bool $is_wide ): void {
	$base  = [ 'class' => 'base' ];
	$extra = [ 'data-extra' => 'yes' ];

	$extra[ 'data-' . sanitize_key( 'Thing' ) ] = 'thing';

	echo get_block_wrapper_attributes( array_merge( $base, $extra, [ 'id' => 'merged' ] ) );
	echo get_block_wrapper_attributes( array_replace( $base, [ 'class' => 'replaced' ] ) );
	echo get_block_wrapper_attributes( $is_wide ? [ 'class' => 'wide' ] : [ 'class' => 'narrow' ] );
	echo get_block_wrapper_attributes( $base ?: [ 'class' => 'fallback' ] );
	echo get_block_wrapper_attributes( $base ?? [ 'class' => 'fallback' ] );
	echo get_block_wrapper_attributes(
		[
			'class' => 'spread',
			...$extra,
		]
	);

	$alias = $base;
	$chain = $alias;
	echo get_block_wrapper_attributes( $chain );

	$merged  = array_merge( $base, $extra );
	$merged += [ 'data-plus' => 'yes' ];
	echo get_block_wrapper_attributes( $merged );

	// Arrow functions capture the enclosing scope by value.
	$render = fn() => get_block_wrapper_attributes( $base );
	echo $render();

	// Language constructs and allowed functions do not modify the array.
	if ( isset( $base['class'] ) && ! empty( $base ) ) {
		$stored = get_block_wrapper_attributes( $base );
	}

	// Writes after the call (outside of a loop) do not matter.
	$later = [ 'class' => 'later' ];
	echo get_block_wrapper_attributes( $later );
	$later[ $is_wide ] = 'ignored';
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
