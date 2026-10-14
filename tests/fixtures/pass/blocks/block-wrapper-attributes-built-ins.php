<?php
/**
 * Test for Alley.PHP.BlockWrapperAttributes with traced arrays passed to built-in PHP functions.
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Built-in PHP functions that do not take the argument by reference.
 */
function alley_block_wrapper_attributes_built_ins(): void {
	$base = [ 'class' => 'base' ];

	// Called normally, globally, with grouping parentheses, or with an element of the array.
	if ( count( $base ) && in_array( 'base', $base, true ) && \count( $base ) && count( ( $base ) ) && strlen( $base['class'] ) ) {
		echo get_block_wrapper_attributes( $base );
	}

	// Named arguments and extra arguments collected by a variadic parameter.
	$merged = [ 'class' => 'merged' ];
	echo esc_html( implode( separator: ' ', array: $merged ) );
	echo esc_html( max( 1, 2, count( $merged ) ) );
	echo get_block_wrapper_attributes( $merged );
}
