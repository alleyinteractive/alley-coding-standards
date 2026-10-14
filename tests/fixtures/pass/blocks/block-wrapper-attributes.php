<?php
/**
 * Test for Alley.PHP.BlockWrapperAttributes with safe attribute names.
 *
 * @global array $attributes Block attributes passed to the render callback.
 *
 * @package Alley\WP\Coding_Standards
 */

$label = $attributes['label'] ?? '';

// Variable built in this scope with literal or sanitized keys.
$wrapper_attributes = [ 'class' => 'card' ];

if ( $label ) {
	$wrapper_attributes['data-label'] = $label;
	$wrapper_attributes['class']     .= ' has-label';
}

$wrapper_attributes[ sanitize_key( $attributes['name'] ?? '' ) ] = $label;

// array_merge() of traced values, including merging into itself.
$data_attributes   = [ 'data-rest-nonce' => wp_create_nonce( 'wp_rest' ) ];
$data_attributes   = array_merge( $data_attributes, [ 'data-label' => $label ] );
$merged_attributes = array_merge( $wrapper_attributes, $data_attributes );

/**
 * Builds wrapper attributes inside a function scope.
 *
 * @param string $id Element ID.
 * @return string
 */
function alley_wrapper_attributes( $id ) {
	$attrs = [ 'id' => $id ];

	return get_block_wrapper_attributes( $attrs );
}

$render = fn () => get_block_wrapper_attributes( [ 'class' => 'arrow' ] );
?>
<div <?php echo get_block_wrapper_attributes(); ?>></div>
<div <?php echo get_block_wrapper_attributes( [] ); ?>></div>
<div <?php echo get_block_wrapper_attributes( [ 'class' => 'alignfull ' . $label ] ); ?>></div>
<div <?php echo get_block_wrapper_attributes( array( 'id' => $label ) ); // phpcs:ignore Generic.Arrays.DisallowLongArraySyntax.Found ?>></div>
<div <?php echo get_block_wrapper_attributes( extra_attributes: [ 'aria-label' => $label ] ); ?>></div>
<div <?php echo get_block_wrapper_attributes( [ sanitize_key( $label ) => 'x' ] ); ?>></div>
<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); ?>></div>
<div <?php echo get_block_wrapper_attributes( $merged_attributes ); ?>></div>
<div <?php echo get_block_wrapper_attributes( array_merge( $data_attributes, [ 'class' => 'tabs-only' ] ) ); ?>></div>
