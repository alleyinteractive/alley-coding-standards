<?php
/**
 * Functions test file.
 *
 * @package Alley\WP\Coding_Standards
 */

$ai_function = function () {
	return 'Hello, World!';
};

echo esc_html( $ai_function() );
