<?php
/**
 * Fail fixture for Alley.PHP.BlockWrapperAttributes at file scope.
 *
 * File-scope variables may already be set by the code that includes the file,
 * so an assignment that might not run does not prove what the keys are.
 *
 * @global array $attributes Block attributes passed to the render callback.
 *
 * @package Alley\WP\Coding_Standards
 */

// Only replaced in preview, so otherwise the block's own attributes are used.
if ( ! empty( $attributes['preview'] ) ) {
	$attributes = [ 'class' => 'preview' ]; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
}
echo get_block_wrapper_attributes( $attributes );

// Assigned in a loop that might not run.
foreach ( $attributes['items'] ?? [] as $alley_item ) {
	$alley_looped = [ 'class' => 'item' ];
}
echo get_block_wrapper_attributes( $alley_looped );

// Assigned in a control structure without braces.
if ( ! empty( $attributes['wide'] ) ) $alley_braceless = [ 'class' => 'wide' ]; // phpcs:ignore Generic.ControlStructures.InlineControlStructure.NotAllowed
echo get_block_wrapper_attributes( $alley_braceless );

// Keeps whatever value was already set.
$alley_existing = $alley_existing ?? [];
echo get_block_wrapper_attributes( $alley_existing );

// Merges into whatever value was already set.
$alley_merged = array_merge( $alley_merged, [ 'class' => 'x' ] );
echo get_block_wrapper_attributes( $alley_merged );
