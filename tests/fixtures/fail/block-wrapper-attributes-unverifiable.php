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
 * @param bool   $flag   A flag.
 */
function alley_block_wrapper_unverifiable( array $param, array $other, object $object, bool $flag ): void {
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

	// Ternaries and null coalescing aren't followed, even with literal branches.
	echo get_block_wrapper_attributes( $flag ? [ 'class' => 'x' ] : [ 'class' => 'y' ] );
	echo get_block_wrapper_attributes( $param ?? [ 'class' => 'x' ] );
	echo get_block_wrapper_attributes( $param += $flag ? [] : [] );

	// Argument unpacking.
	echo get_block_wrapper_attributes( ...$other );
}
