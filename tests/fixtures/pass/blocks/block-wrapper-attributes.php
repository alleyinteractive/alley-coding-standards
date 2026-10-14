<?php
/**
 * Test for Alley.PHP.BlockWrapperAttributes in a block render template.
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

$wrapper_attributes[ sanitize_key( $attributes['dataKey'] ) ] = $attributes['dataValue'];

$wrapper_attributes[ 'data-' . sanitize_key( $attributes['suffix'] ) ] = 'yes';
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); ?>>
	<?php echo wp_kses_post( $content ); ?>
</div>
<?php

// No arguments, or an empty array.
echo get_block_wrapper_attributes();
echo get_block_wrapper_attributes( [] );

// Fully qualified call with an inline literal and dynamic values.
echo \get_block_wrapper_attributes( [ 'id' => $attributes['anchor'] ] );

// Case-insensitive function name.
echo GET_BLOCK_WRAPPER_ATTRIBUTES( [ 'class' => 'upper' ] );

// Named argument.
echo get_block_wrapper_attributes( extra_attributes: [ 'class' => 'named' ] );

// Integer keys, sanitize_key() keys, and safe concatenations inline.
echo get_block_wrapper_attributes(
	[
		0                                          => 'zero',
		sanitize_key( $attributes['key'] )         => 'a',
		'data-' . sanitize_key( $attributes['k'] ) => 'b',
		( 'data-grouped' )                         => 'c',
	]
);

/**
 * Merging, replacing, ternaries, null coalescing, spreads and variable chains.
 *
 * @param bool $is_wide Whether the block is wide.
 */
function alley_block_wrapper_attributes_shapes( bool $is_wide ): void {
	$base  = [ 'class' => 'base' ];
	$extra = [ 'data-extra' => 'yes' ];

	$extra[ sanitize_key( 'Data-Thing' ) ] = 'thing';

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

	// Read-only uses that do not mutate the array, including built-in PHP
	// functions that do not take the argument by reference.
	if ( isset( $base['class'] ) && ! empty( $base ) && count( $base ) && in_array( 'base', $base, true ) ) {
		$stored = get_block_wrapper_attributes( $base );
	}

	// Writes after the call (outside of a loop) do not matter.
	$later = [ 'class' => 'later' ];
	echo get_block_wrapper_attributes( $later );
	$later[ $is_wide ] = 'ignored';
}

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
