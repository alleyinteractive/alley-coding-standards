<?php
/**
 * Database test file.
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Example function with a database call.
 */
function ai_example_function() {
	global $wpdb;

	$wpdb->get_results( 'SELECT * FROM wp_posts' );
}
