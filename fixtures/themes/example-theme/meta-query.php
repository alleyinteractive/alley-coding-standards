<?php
/**
 * Database test file.
 *
 * @package Alley\WP\Coding_Standards
 */

global $wpdb;

$ai_handle = fopen( 'php://stdin', 'r' );
$ai_line = fgets( $ai_handle );

fclose( $ai_handle );

$ai_var = $_GET['asdad'] ?? '';

$wpdb->get_results( 'SELECT * FROM wp_posts' );

$ai_query = new WP_Query( ['meta_key' => 'foo' ] );

$ai_get_posts = get_posts( [
	'post_type' => 'post',
	'posts_per_page' => 10,
	'meta_query' => [
		[
			'key' => 'foo',
			'value' => 'bar',
			'compare' => '=',
		],
	],
] );
