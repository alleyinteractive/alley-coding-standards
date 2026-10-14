<?php
/**
 * Fail fixture for Alley.PHP.BlockWrapperAttributes.UnverifiableKeys.
 *
 * Every get_block_wrapper_attributes() call in this file is passed an array
 * whose keys cannot be determined.
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Opaque arrays.
 *
 * @param array  $param  A parameter.
 * @param array  $other  Another array.
 * @param object $object An object.
 */
function alley_block_wrapper_unverifiable( array $param, array $other, object $object ): void {
	// Object property.
	echo get_block_wrapper_attributes( $object->attrs );

	// Function parameter.
	echo get_block_wrapper_attributes( $param );

	// Helper return value, inline and assigned.
	echo get_block_wrapper_attributes( alley_get_attrs() );
	$from_helper = alley_get_attrs();
	echo get_block_wrapper_attributes( $from_helper );

	// Untraced variable.
	echo get_block_wrapper_attributes( $never_assigned );

	// Element writes without a known base.
	$param['class'] = 'x';
	echo get_block_wrapper_attributes( $param );

	// Union with an opaque array.
	$union  = [ 'class' => 'x' ];
	$union += $other;
	echo get_block_wrapper_attributes( $union );

	// Passed to another function first, which could modify it by reference.
	$passed = [ 'class' => 'x' ];
	alley_modify_attrs( $passed );
	echo get_block_wrapper_attributes( $passed );

	// Passed in grouping parentheses, which still passes it by reference.
	$grouped = [ 'class' => 'x' ];
	alley_modify_attrs( ( $grouped ) );
	echo get_block_wrapper_attributes( $grouped );

	// A namespaced function, which isn't the built-in with the same name.
	$qualified = [ 'class' => 'x' ];
	\Alley\Project\count( $qualified );
	echo get_block_wrapper_attributes( $qualified );

	// Passed by reference to a built-in PHP function.
	$sorted = [ 'class' => 'x' ];
	ksort( $sorted );
	echo get_block_wrapper_attributes( $sorted );

	// Destructured.
	[ $destructured ] = $other;
	echo get_block_wrapper_attributes( $destructured );

	// Captured by reference in a closure.
	$captured = [ 'class' => 'x' ];
	$modify   = function () use ( &$captured ) {
		$captured['class'] = 'y';
	};
	$modify();
	echo get_block_wrapper_attributes( $captured );

	// Spread of an opaque array.
	echo get_block_wrapper_attributes(
		[
			'class' => 'x',
			...$other,
		]
	);

	// Opaque array inside array_merge().
	echo get_block_wrapper_attributes( array_merge( [ 'class' => 'x' ], $other ) );

	// Argument unpacking.
	echo get_block_wrapper_attributes( ...$other );

	// Arrow function parameter that shadows a safe variable in the enclosing scope.
	$shadowed = [ 'class' => 'x' ];
	echo implode( '', array_map( fn( $shadowed ) => get_block_wrapper_attributes( $shadowed ), [ $other ] ) );
}

/**
 * Changed by extract().
 *
 * @param array $other Another array.
 */
function alley_block_wrapper_unverifiable_extract( array $other ): void {
	$attrs = [ 'class' => 'x' ];
	extract( $other );
	echo get_block_wrapper_attributes( $attrs );
}

/**
 * Changed by an included file.
 */
function alley_block_wrapper_unverifiable_include(): void {
	$attrs = [ 'class' => 'x' ];
	require __DIR__ . '/partial.php';
	echo get_block_wrapper_attributes( $attrs );
}

/**
 * Changed by eval().
 *
 * @param string $code Code to evaluate.
 */
function alley_block_wrapper_unverifiable_eval( string $code ): void {
	$attrs = [ 'class' => 'x' ];
	eval( $code );
	echo get_block_wrapper_attributes( $attrs );
}

/**
 * Changed through a variable variable.
 *
 * @param string $name A variable name.
 */
function alley_block_wrapper_unverifiable_variable_variable( string $name ): void {
	$attrs = [ 'class' => 'x' ];
	$$name = [];
	echo get_block_wrapper_attributes( $attrs );
}

/**
 * Changed through $GLOBALS.
 */
function alley_block_wrapper_unverifiable_globals(): void {
	$attrs = [ 'class' => 'x' ];

	$GLOBALS['alley_attrs'] = [];
	echo get_block_wrapper_attributes( $attrs );
}
