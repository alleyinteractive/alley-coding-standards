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

// Inline closure — return type.
add_filter( 'the_content', function ( $content ) {
	return (string) $content;
} );

// Static closure — parameter typehint.
add_action( 'wp_loaded', static function ( $arg ) {
	// Body.
} );

// Arrow function — parameter typehint and return type.
add_filter( 'the_title', fn( $title ) => strtoupper( $title ) );
