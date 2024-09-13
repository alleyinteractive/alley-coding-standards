<?php
/**
 * Database test file.
 *
 * @package Alley\WP\Coding_Standards
 */

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
