<?php
/**
 * Named filter callback typehint fail fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

// Named function with parameter typehint registered as a string callback.
function alley_typed_named_callback( string $arg ): string {
	return $arg;
}
add_filter( 'the_title', 'alley_typed_named_callback' );
