<?php
/**
 * Closure/arrow callback typehint fail fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

// Inline closure — parameter typehint.
add_action( 'init', function ( string $arg ) {
	// Body.
} );

// Static closure — parameter typehint.
add_action( 'wp_loaded', static function ( string $arg ) {
	// Body.
} );

// Arrow function — parameter typehint.
add_filter( 'the_title', fn( string $title ): string => strtoupper( $title ) );
