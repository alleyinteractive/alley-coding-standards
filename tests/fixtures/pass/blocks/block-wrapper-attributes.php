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

// Assigned at the top level and used in a branch, or assigned and used in the same branch.
$branch_attributes = [ 'class' => 'card' ];

if ( count( $branch_attributes ) && in_array( 'card', $branch_attributes, true ) ) {
	echo get_block_wrapper_attributes( $branch_attributes );

	$inner_attributes = [ 'id' => $label ];

	echo get_block_wrapper_attributes( $inner_attributes );
}

// Common reads of a traced array.
$read_attributes = [ 'class' => 'card' ];

if ( $read_attributes && isset( $read_attributes['class'] ) && ! empty( $read_attributes['class'] ) ) {
	$read_attributes['class'] .= ' ' . esc_attr( $read_attributes['class'] ?? '' );
}

foreach ( $read_attributes as $read_name => $read_value ) {
	unset( $read_attributes['style'] );
}

$read_copy = implode( ' ', array_keys( $read_attributes ) ) . wp_json_encode( $read_attributes );

echo get_block_wrapper_attributes( $read_attributes );

// Repeated self-merges must not be traced exponentially.
$many_merges = [ 'class' => 'card' ];

$many_merges = array_merge( $many_merges, [ 'data-1' => '1' ] );
$many_merges = array_merge( $many_merges, [ 'data-2' => '2' ] );
$many_merges = array_merge( $many_merges, [ 'data-3' => '3' ] );
$many_merges = array_merge( $many_merges, [ 'data-4' => '4' ] );
$many_merges = array_merge( $many_merges, [ 'data-5' => '5' ] );
$many_merges = array_merge( $many_merges, [ 'data-6' => '6' ] );
$many_merges = array_merge( $many_merges, [ 'data-7' => '7' ] );
$many_merges = array_merge( $many_merges, [ 'data-8' => '8' ] );
$many_merges = array_merge( $many_merges, [ 'data-9' => '9' ] );
$many_merges = array_merge( $many_merges, [ 'data-10' => '10' ] );
$many_merges = array_merge( $many_merges, [ 'data-11' => '11' ] );
$many_merges = array_merge( $many_merges, [ 'data-12' => '12' ] );
$many_merges = array_merge( $many_merges, [ 'data-13' => '13' ] );
$many_merges = array_merge( $many_merges, [ 'data-14' => '14' ] );
$many_merges = array_merge( $many_merges, [ 'data-15' => '15' ] );
$many_merges = array_merge( $many_merges, [ 'data-16' => '16' ] );
$many_merges = array_merge( $many_merges, [ 'data-17' => '17' ] );
$many_merges = array_merge( $many_merges, [ 'data-18' => '18' ] );
$many_merges = array_merge( $many_merges, [ 'data-19' => '19' ] );
$many_merges = array_merge( $many_merges, [ 'data-20' => '20' ] );
$many_merges = array_merge( $many_merges, [ 'data-21' => '21' ] );
$many_merges = array_merge( $many_merges, [ 'data-22' => '22' ] );
$many_merges = array_merge( $many_merges, [ 'data-23' => '23' ] );
$many_merges = array_merge( $many_merges, [ 'data-24' => '24' ] );
$many_merges = array_merge( $many_merges, [ 'data-25' => '25' ] );
$many_merges = array_merge( $many_merges, [ 'data-26' => '26' ] );
$many_merges = array_merge( $many_merges, [ 'data-27' => '27' ] );
$many_merges = array_merge( $many_merges, [ 'data-28' => '28' ] );
$many_merges = array_merge( $many_merges, [ 'data-29' => '29' ] );
$many_merges = array_merge( $many_merges, [ 'data-30' => '30' ] );

echo get_block_wrapper_attributes( $many_merges );
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
