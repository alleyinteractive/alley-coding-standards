<?php
/**
 * Filter callback typehint fail fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

// Inline closure — parameter typehint.
add_filter( 'the_title', function ( string $arg ) {
	return $arg;
} );

// Static closure — parameter typehint.
add_filter( 'the_excerpt', static function ( string $arg ) {
	return $arg;
} );

// Arrow function — parameter typehint.
add_filter( 'the_title', fn( string $title ): string => strtoupper( $title ) );

// Multi-param closure — first parameter typehint is flagged; second is ignored.
add_filter( 'the_content', function ( string $content, string $extra ) {
	return $content . $extra;
} );
