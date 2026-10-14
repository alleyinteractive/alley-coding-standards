<?php
/**
 * Fail fixture for Alley.PHP.BlockWrapperAttributes.UnescapedDynamicKey.
 *
 * Every get_block_wrapper_attributes() call in this file has a visible key that
 * is not a literal or wrapped in sanitize_key().
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Dynamic keys.
 *
 * @param string $key   A dynamic key.
 * @param bool   $flag  A flag.
 * @param array  $items Items to loop over.
 */
function alley_block_wrapper_dynamic_keys( string $key, bool $flag, array $items ): void {
	// Inline variable key.
	echo get_block_wrapper_attributes( [ $key => 'value' ] );

	// Interpolated string key.
	echo get_block_wrapper_attributes( [ "data-{$key}" => 'value' ] );

	// esc_attr() does not make an attribute name safe.
	echo get_block_wrapper_attributes( [ esc_attr( $key ) => 'value' ] );

	// Class constant key.
	echo get_block_wrapper_attributes( [ Alley_Block::ATTR => 'value' ] );

	// Global constant key.
	echo get_block_wrapper_attributes( [ ALLEY_BLOCK_ATTR => 'value' ] );

	// Concatenation with an unsanitized part.
	echo get_block_wrapper_attributes( [ 'data-' . $key => 'value' ] );

	// sanitize_key() alone still allows event handlers such as onclick.
	echo get_block_wrapper_attributes( [ sanitize_key( $key ) => 'value' ] );

	// A prefix without a hyphen can still form an event handler.
	echo get_block_wrapper_attributes( [ 'on' . sanitize_key( $key ) => 'value' ] );

	// The hyphenated literal has to come first.
	echo get_block_wrapper_attributes( [ sanitize_key( $key ) . '-data' => 'value' ] );

	// Traced variable with a bare sanitize_key() key.
	$sanitized                         = [ 'class' => 'x' ];
	$sanitized[ sanitize_key( $key ) ] = 'value';
	echo get_block_wrapper_attributes( $sanitized );

	// Traced variable with a dynamic key.
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

	// Dynamic key inside a ternary branch.
	echo get_block_wrapper_attributes( $flag ? [ 'class' => 'x' ] : [ $key => 'y' ] );

	// Dynamic key inside an array_merge() argument.
	echo get_block_wrapper_attributes( array_merge( [ 'class' => 'x' ], [ $key => 'y' ] ) );

	// Dynamic key in a conditional write.
	$conditional = [ 'class' => 'x' ];
	if ( $flag ) {
		$conditional[ $key ] = 'y';
	}
	echo get_block_wrapper_attributes( $conditional );
}
