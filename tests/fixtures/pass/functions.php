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

// Allow function arguments on the same line as the function call.
// Supports PSR2.Methods.FunctionCallSignature.
ai_method_call( [
	'foo' => 'bar',
] );

ai_method_call(
	[
		'foo' => 'bar',
	]
);

ai_method_call( [ 'foo' => 'bar' ] );
ai_method_call();
