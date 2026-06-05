<?php
/**
 * Action/filter callback typehint pass fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

// Closure without typehints — must not be flagged.
add_action( 'init', function ( $arg ) {
	// Body.
} );

// Closure without return type — must not be flagged.
add_filter( 'the_content', function ( $content ) {
	return (string) $content;
} );

// Static closure without typehints — must not be flagged.
add_action( 'wp_loaded', static function ( $arg ) {
	// Body.
} );

// Arrow function without typehints — must not be flagged.
add_filter( 'the_title', fn( $title ) => strtoupper( $title ) );

// Named function without typehints, registered as a string callback — must not be flagged.
function alley_untyped_callback( $arg ) {}
add_action( 'plugins_loaded', 'alley_untyped_callback' );

// Function WITH typehints that is NOT registered as a callback — must not be flagged.
function alley_utility_function( string $arg ): bool {
	return strlen( $arg ) > 0;
}
