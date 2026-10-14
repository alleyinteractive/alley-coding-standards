<?php
/**
 * Test for Alley.PHP.BlockWrapperAttributes with dynamic attribute names.
 *
 * @package Alley\WP\Coding_Standards
 */

$alley_name = sanitize_text_field( wp_unslash( $_GET['name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// Inline array literals.
echo get_block_wrapper_attributes( [ $alley_name => 'x' ] );
echo get_block_wrapper_attributes( [ 'data-' . $alley_name => 'x' ] );
echo get_block_wrapper_attributes( [ "data-{$alley_name}" => 'x' ] );
echo get_block_wrapper_attributes( extra_attributes: [ $alley_name => 'x' ] );

// Traced variables.
$alley_attrs = [ 'class' => 'a' ];

$alley_attrs[ $alley_name ] = 'x';

echo get_block_wrapper_attributes( $alley_attrs );

$alley_built = [ $alley_name => 'x' ];

echo get_block_wrapper_attributes( $alley_built );

// Unsafe key inside a merged array.
echo get_block_wrapper_attributes( array_merge( $alley_built, [ $alley_name => 'y' ] ) );
