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

	// Traced variable with a dynamic key.
	$attrs         = [ 'class' => 'x' ];
	$attrs[ $key ] = 'value';
	echo get_block_wrapper_attributes( $attrs );

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
