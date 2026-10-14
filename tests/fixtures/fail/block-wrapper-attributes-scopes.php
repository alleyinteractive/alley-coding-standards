<?php
/**
 * Fail fixture for Alley.PHP.BlockWrapperAttributes with variables changed outside plain writes.
 *
 * Every get_block_wrapper_attributes() call in this file is passed a variable
 * that a reference, destructuring, loop, declaration or nested scope can change.
 *
 * @package Alley\WP\Coding_Standards
 */

/**
 * Variables changed by something other than an assignment or element write.
 *
 * @param array $other Another array.
 */
function alley_block_wrapper_scopes( array $other ): void {
	// Destructured, in both syntaxes.
	[ $destructured ] = $other;
	echo get_block_wrapper_attributes( $destructured );

	list( $listed ) = $other;
	echo get_block_wrapper_attributes( $listed );

	// A foreach loop variable.
	foreach ( $other as $looped ) {
		echo get_block_wrapper_attributes( $looped );
	}

	// Changed through a reference.
	$referenced = [ 'class' => 'x' ];
	$alias      = &$referenced;
	echo get_block_wrapper_attributes( $referenced );

	// Captured by reference in a closure.
	$captured = [ 'class' => 'x' ];
	$modify   = function () use ( &$captured ) {
		$captured['class'] = 'y';
	};
	$modify();
	echo get_block_wrapper_attributes( $captured );

	// Arrow function parameter that shadows a safe variable in the enclosing scope.
	$shadowed = [ 'class' => 'x' ];
	echo implode( '', array_map( fn( $shadowed ) => get_block_wrapper_attributes( $shadowed ), [ $other ] ) );

	// Copied from a variable whose keys are unknown.
	$copied = $other;
	echo get_block_wrapper_attributes( $copied );
}

/**
 * Global and static variables.
 */
function alley_block_wrapper_declarations(): void {
	global $alley_global_attrs;
	echo get_block_wrapper_attributes( $alley_global_attrs );

	static $cached;
	echo get_block_wrapper_attributes( $cached );
}

/**
 * Changed through extract() called globally.
 *
 * @param array $other Another array.
 */
function alley_block_wrapper_global_extract( array $other ): void {
	$attrs = [ 'class' => 'x' ];
	\extract( $other );
	echo get_block_wrapper_attributes( $attrs );
}
