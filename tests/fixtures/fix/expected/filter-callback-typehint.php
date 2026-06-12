<?php
/**
 * Filter callback typehint fix fixture.
 *
 * Each violation here is auto-fixable; after phpcbf all typehints must be gone.
 *
 * @package Alley\WP\Coding_Standards
 */

// Inline closure — parameter typehint.
add_filter( 'the_title', function ( $arg ) {
	return $arg;
} );

// Static closure — parameter typehint.
add_filter( 'the_excerpt', static function ( $arg ) {
	return $arg;
} );

// Arrow function — parameter typehint.
add_filter( 'the_title', fn( $title ): string => strtoupper( $title ) );

// Multi-param closure — only first parameter typehint should be removed.
add_filter( 'the_content', function ( $content, string $extra ) {
	return $content . $extra;
} );
