<?php
/**
 * Functions test file.
 *
 * @package Alley\WP\Coding_Standards
 */

$alley_function = function () {
	return 'Hello, World!';
};

echo esc_html( $alley_function() );

// Allow function arguments on the same line as the function call.
// Supports PSR2.Methods.FunctionCallSignature.
ai_method_call( [
	'foo' => 'bar',
] );

ai_method_call( [
	'foo'     => 'bar',
	'another' => 'test',
	'depth'   => [
		'one' => 'two',
	],
] );

ai_method_call(
	[
		'foo' => 'bar',
	]
);

ai_method_call( [ 'foo' => 'bar' ] );
ai_method_call();
