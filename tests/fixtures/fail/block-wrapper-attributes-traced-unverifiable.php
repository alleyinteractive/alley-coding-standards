<?php
/**
 * Fail fixture for Alley.PHP.BlockWrapperAttributes.UnverifiableKeys in traced variables.
 *
 * Every get_block_wrapper_attributes() call in this file is passed a variable
 * whose keys cannot be determined.
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Opaque variables.
 *
 * @param array $param A parameter.
 * @param array $other Another array.
 */
function alley_block_wrapper_traced_unverifiable( array $param, array $other ): void {
	// Helper return value, assigned.
	$from_helper = alley_get_attrs();
	echo get_block_wrapper_attributes( $from_helper );

	// Element writes without a known base.
	$param['class'] = 'x';
	echo get_block_wrapper_attributes( $param );

	// Union with an opaque array.
	$union  = [ 'class' => 'x' ];
	$union += $other;
	echo get_block_wrapper_attributes( $union );

	// Changed with another operator.
	$changed  = [ 'class' => 'x' ];
	$changed .= 'y';
	echo get_block_wrapper_attributes( $changed );

	// Passed to another function first, which could modify it by reference.
	$passed = [ 'class' => 'x' ];
	alley_modify_attrs( $passed );
	echo get_block_wrapper_attributes( $passed );

	// Never assigned in this function.
	echo get_block_wrapper_attributes( $never_assigned );

	// Only assigned from itself.
	$self = $self ?? $other;
	echo get_block_wrapper_attributes( $self );
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
