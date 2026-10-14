<?php
/**
 * Fail fixture for Alley.PHP.BlockWrapperAttributes.UnverifiableKeys.
 *
 * Every get_block_wrapper_attributes() call in this file is passed an array
 * whose keys cannot be determined.
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Opaque arrays.
 *
 * @param array  $param  A parameter.
 * @param array  $other  Another array.
 * @param object $object An object.
 */
function alley_block_wrapper_unverifiable( array $param, array $other, object $object ): void {
	// Object property.
	echo get_block_wrapper_attributes( $object->attrs );

	// Function parameter.
	echo get_block_wrapper_attributes( $param );

	// Helper return value.
	echo get_block_wrapper_attributes( alley_get_attrs() );

	// Untraced variable.
	echo get_block_wrapper_attributes( $never_assigned );

	// Spread of an opaque array.
	echo get_block_wrapper_attributes(
		[
			'class' => 'x',
			...$other,
		]
	);

	// Opaque array inside array_merge().
	echo get_block_wrapper_attributes( array_merge( [ 'class' => 'x' ], $other ) );

	// Opaque branch of a null coalescing expression.
	echo get_block_wrapper_attributes( $param ?? [ 'class' => 'x' ] );

	// Argument unpacking.
	echo get_block_wrapper_attributes( ...$other );
}
