<?php
/**
 * Closure/arrow callback typehint fix fixture.
 *
 * Each violation here is auto-fixable; after phpcbf all typehints must be gone.
 *
 * @package Alley\WP\Coding_Standards
 */

// Inline closure — parameter typehint.
add_action( 'init', function ( $arg ) {
	// Body.
} );

// Static closure — parameter typehint.
add_action( 'wp_loaded', static function ( $arg ) {
	// Body.
} );

// Arrow function — parameter typehint.
add_filter( 'the_title', fn( $title ): string => strtoupper( $title ) );
