<?php
/**
 * Named callback typehint fail fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

// Named function with parameter typehint and return type, registered as a string callback.
function alley_typed_named_callback( string $arg ): void {}
add_action( 'init', 'alley_typed_named_callback' );
