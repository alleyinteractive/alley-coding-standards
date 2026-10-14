<?php
/**
 * Fail fixture for Alley.PHP.BlockWrapperAttributes with traced arrays passed to functions.
 *
 * Every get_block_wrapper_attributes() call in this file is passed an array
 * that a function might have modified by reference first.
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Functions that take the array by reference, or aren't known to be built-ins.
 *
 * @param string $subject A string to match.
 */
function alley_block_wrapper_built_ins( string $subject ): void {
	// Passed by reference to a built-in PHP function.
	$sorted = [ 'class' => 'x' ];
	ksort( $sorted );
	echo get_block_wrapper_attributes( $sorted );

	// Passed by reference in a later position.
	$matches = [ 'class' => 'x' ];
	preg_match( '/(?<onclick>.+)/', $subject, $matches );
	echo get_block_wrapper_attributes( $matches );

	// A namespaced function, which isn't the built-in with the same name.
	$qualified = [ 'class' => 'x' ];
	\Alley\Project\count( $qualified );
	echo get_block_wrapper_attributes( $qualified );
}
