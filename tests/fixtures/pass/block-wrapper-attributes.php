<?php
/**
 * Block wrapper attributes pass fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

// Inline array with string literal keys — must not be flagged.
echo get_block_wrapper_attributes( [ 'class' => $class_name ] );

// Inline array with a fully-qualified call — must not be flagged.
echo \get_block_wrapper_attributes( [ 'data-id' => $id ] );

// Long array syntax with sanitize_key() wrapping the key — must not be flagged.
// The sniff reads array() separately from [], so it has its own pass and fail cases.
echo get_block_wrapper_attributes( array( sanitize_key( $key ) => $value ) ); // phpcs:ignore Generic.Arrays.DisallowLongArraySyntax.Found

// No arguments — must not be flagged.
echo get_block_wrapper_attributes();

// Variable built up at file scope with safe keys — must not be flagged.
$alley_attrs                         = [ 'class' => 'wp-block-alley' ];
$alley_attrs['id']                   = $id;
$alley_attrs[ sanitize_key( $key ) ] = $value;
$alley_attrs['style']               .= 'color: red;';
$alley_attrs[]                       = 'list-item';

if ( isset( $alley_attrs['id'] ) ) {
	$alley_attrs['data-has-id'] = 'true';
}

// Reads that cannot change the keys — must not be flagged.
if ( ! empty( $alley_attrs ) ) {
	echo esc_html( $alley_attrs['id'] );
}

echo get_block_wrapper_attributes( $alley_attrs );

// Safe keys added after the call inside a loop — must not be flagged.
$alley_loop = [ 'class' => 'wp-block-alley' ];
foreach ( $list as $alley_loop_value ) {
	echo get_block_wrapper_attributes( $alley_loop );
	$alley_loop['data-value'] = $alley_loop_value;
}

// Arrow function using a variable from the outer scope — must not be flagged.
$alley_arrow = [ 'class' => 'wp-block-alley' ];
$alley_html  = array_map( fn( $item ) => $item . get_block_wrapper_attributes( $alley_arrow ), $list );

/**
 * Variable built up inside a function with safe keys — must not be flagged.
 *
 * @param string $key Key.
 */
function alley_render_block( $key ) {
	$attributes = [
		'class'              => 'wp-block-alley',
		sanitize_key( $key ) => 'value',
	];

	// Unsafe key in a nested closure belongs to a different variable — must not be flagged.
	$callback = function () {
		$attributes = [ 'data-ok' => 'yes' ];
		return $attributes;
	};

	return get_block_wrapper_attributes( $attributes );
}

// Method call with the same name — must not be flagged.
echo $block->get_block_wrapper_attributes( $anything );
