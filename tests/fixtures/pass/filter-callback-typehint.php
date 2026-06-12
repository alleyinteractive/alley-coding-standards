<?php
/**
 * Filter callback typehint pass fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

// Closure without typehints — must not be flagged.
add_action( 'init', function ( $arg ) {
	// Body.
} );

// Closure without parameter typehint — must not be flagged.
add_filter( 'the_content', function ( $content ) {
	return (string) $content;
} );

// Closure with return type but no parameter typehint — must not be flagged.
add_filter( 'the_content', function ( $content ): string {
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

// add_action callbacks WITH typehints — must not be flagged (actions are not checked).
add_action( 'init', function ( string $arg ) {
	// Body.
} );

add_action( 'save_post', static function ( int $post_id ) {
	// Body.
} );

function alley_typed_action_callback( string $hook ): void {}
add_action( 'plugins_loaded', 'alley_typed_action_callback' );

// First parameter with mixed typehint — must not be flagged.
add_filter( 'the_title', function ( mixed $title ) {
	return (string) $title;
} );

// Second parameter with typehint, first without — must not be flagged.
add_filter( 'the_content', function ( $content, string $extra ) {
	return $content . $extra;
} );

// Mixed first parameter with typehint on second — must not be flagged.
add_filter( 'the_content', function ( mixed $content, string $extra ) {
	return $content . $extra;
} );
