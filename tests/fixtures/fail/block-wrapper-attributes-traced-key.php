<?php
/**
 * Fail fixture for Alley.PHP.BlockWrapperAttributes.UnescapedDynamicKey in traced variables.
 *
 * Every get_block_wrapper_attributes() call in this file is passed a variable
 * with an unsafe key written to it before the call.
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Dynamic keys written to traced variables.
 *
 * @param string $key   A dynamic key.
 * @param bool   $flag  A flag.
 * @param array  $items Items to loop over.
 */
function alley_block_wrapper_traced_keys( string $key, bool $flag, array $items ): void {
	// Bare sanitize_key() key.
	$sanitized                         = [ 'class' => 'x' ];
	$sanitized[ sanitize_key( $key ) ] = 'value';
	echo get_block_wrapper_attributes( $sanitized );

	// Dynamic key.
	$attrs         = [ 'class' => 'x' ];
	$attrs[ $key ] = 'value';
	echo get_block_wrapper_attributes( $attrs );

	// Element passed to a function, which creates the key if it takes it by reference.
	$touched = [ 'class' => 'x' ];
	alley_touch( $touched[ $key ] );
	echo get_block_wrapper_attributes( $touched );

	// Dynamic key written in a foreach loop.
	$looped = [];
	foreach ( $items as $item_key => $item_value ) {
		$looped[ $item_key ] = $item_value;
	}
	echo get_block_wrapper_attributes( $looped );

	// Dynamic key in a conditional write.
	$conditional = [ 'class' => 'x' ];
	if ( $flag ) {
		$conditional[ $key ] = 'y';
	}
	echo get_block_wrapper_attributes( $conditional );
}
