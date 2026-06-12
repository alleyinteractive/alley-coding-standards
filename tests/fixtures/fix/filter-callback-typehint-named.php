<?php
/**
 * Named filter callback typehint fix fixture.
 *
 * Each violation here is auto-fixable; after phpcbf the first parameter typehint must be gone.
 *
 * @package Alley\WP\Coding_Standards
 */

// Named function with parameter typehint registered as a string callback.
function alley_typed_named_fix_callback( string $arg ): string {
	return $arg;
}
add_filter( 'the_title', 'alley_typed_named_fix_callback' );
