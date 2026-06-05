<?php
/**
 * Static class method callback typehint pass fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

class Alley_Static_Method_Callback_Pass {

	public function setup() {
		// String callback 'ClassName::method' — method has no typehints.
		add_action( 'init', 'Alley_Static_Method_Callback_Pass::on_init' );

		// Array callback [ 'ClassName', 'method' ] — method has no typehints.
		add_filter( 'the_title', [ 'Alley_Static_Method_Callback_Pass', 'filter_title' ] );

		// Array callback [ self::class, 'method' ] — method has no typehints.
		add_action( 'wp_loaded', [ self::class, 'on_loaded' ] );

		// First-class callable self::method(...) — method has no typehints.
		add_filter( 'the_content', self::filter_content( ... ) );
	}

	public static function on_init( $arg ) {}

	public static function filter_title( $title ) {
		return $title;
	}

	public static function on_loaded( $arg ) {}

	public static function filter_content( $content ) {
		return $content;
	}
}
