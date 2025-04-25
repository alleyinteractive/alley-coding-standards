<?php
/**
 * Yoda test file.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package Alley\WP\Coding_Standards
 */

$yoda_post_type = 'post';

if ( $yoda_post_type == 'post' ) {
	$category = get_the_category();
}

if ( 'post' == $yoda_post_type ) {
	$category = get_the_category();
}
