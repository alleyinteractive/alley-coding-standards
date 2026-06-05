<?php
/**
 * Named callback typehint fix fixture.
 *
 * Each violation here is auto-fixable; after phpcbf all typehints must be gone.
 *
 * @package Alley\WP\Coding_Standards
 */

// Named function with parameter typehint and return type, registered as a string callback.
function alley_typed_named_fix_callback( string $arg ): void {}
add_action( 'init', 'alley_typed_named_fix_callback' );
