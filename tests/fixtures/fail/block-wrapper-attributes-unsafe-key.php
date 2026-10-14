<?php
/**
 * Block wrapper attributes unsafe key fail fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

// Dynamic key in an inline array.
echo get_block_wrapper_attributes( [ $alley_key => $value ] );

// Interpolated string key.
echo get_block_wrapper_attributes( [ "data-{$alley_key}" => $value ] );

// Concatenated key, even with sanitize_key() on part of it.
echo get_block_wrapper_attributes( [ 'data-' . sanitize_key( $alley_key ) => $value ] );

// Constant key.
echo get_block_wrapper_attributes( [ ALLEY_ATTR => $value ] );

// Dynamic key added to a variable, as in the original issue.
$alley_attrs            = [ 'class' => 'wp-block-alley' ];
$alley_attrs[ $my_key ] = $my_value;
echo get_block_wrapper_attributes( $alley_attrs );

// Dynamic key in a branch of a function.
function alley_render_block_unsafe() {
	if ( $condition ) {
		$attributes = [ esc_attr( $alley_key ) => 'x' ];
	} else {
		$attributes = [ 'class' => 'y' ];
	}

	return get_block_wrapper_attributes( $attributes );
}

// Dynamic key added after the call, which runs before the next iteration.
$alley_loop = [ 'class' => 'x' ];
foreach ( $list as $alley_loop_key => $alley_loop_value ) {
	echo get_block_wrapper_attributes( $alley_loop );
	$alley_loop[ $alley_loop_key ] = $alley_loop_value;
}
