<?php
/**
 * Fail fixture for Alley.PHP.BlockWrapperAttributes with traced arrays used in calls.
 *
 * Every get_block_wrapper_attributes() call in this file is passed an array
 * that a call before it could have modified by reference.
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Calls that receive the array or one of its elements.
 *
 * @param string $key    A dynamic key.
 * @param object $object An object.
 */
function alley_block_wrapper_calls( string $key, object $object ): void {
	// Read-only built-ins count too, so results don't depend on the PHP install.
	$counted = [ 'class' => 'x' ];
	if ( count( $counted ) ) {
		echo get_block_wrapper_attributes( $counted );
	}

	// Passed in grouping parentheses, which still passes it by reference.
	$grouped = [ 'class' => 'x' ];
	alley_modify_attrs( ( $grouped ) );
	echo get_block_wrapper_attributes( $grouped );

	// An element, which creates the key if the parameter is by reference.
	$touched = [ 'class' => 'x' ];
	alley_touch( $touched[ $key ] );
	echo get_block_wrapper_attributes( $touched );

	// A namespaced function, which isn't the allowed function with the same name.
	$qualified = [ 'class' => 'x' ];
	$qualified = \Alley\Project\array_merge( $qualified, [] );
	echo get_block_wrapper_attributes( $qualified );

	// A method with an allowed function's name.
	$method = [ 'class' => 'x' ];
	$object->array_merge( $method );
	echo get_block_wrapper_attributes( $method );

	// Inside an allowed call, but nested in another call.
	$nested = [ 'class' => 'x' ];
	$copy   = array_merge( alley_filter_attrs( $nested ), [] );
	echo get_block_wrapper_attributes( $nested );
	echo esc_html( count( $copy ) );
}
