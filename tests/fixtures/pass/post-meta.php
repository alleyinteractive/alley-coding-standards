<?php
/**
 * WordPress.WP.GetMetaSingle.Missing sniff test.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package Alley\WP\Coding_Standards
 */

$meta = get_post_meta( 123, 'my_meta_key', true );

$other = get_post_meta( 123, 'my_other_meta_key' );
