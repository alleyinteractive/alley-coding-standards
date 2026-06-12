<?php
/**
 * Static class method callback typehint pass fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

class Alley_Static_Method_Callback_Pass {

	public function setup() {
		// String callback 'ClassName::method' registered via add_action — method has typehints — must not be flagged.
		add_action( 'init', 'Alley_Static_Method_Callback_Pass::on_init' );

		// Array callback [ 'ClassName', 'method' ] — method has no typehints — must not be flagged.
		add_filter( 'the_title', [ 'Alley_Static_Method_Callback_Pass', 'filter_title' ] );

		// Array callback [ self::class, 'method' ] registered via add_action — method has typehints — must not be flagged.
		add_action( 'wp_loaded', [ self::class, 'on_loaded' ] );

		// First-class callable self::method(...) — method has no typehints — must not be flagged.
		add_filter( 'the_content', self::filter_content( ... ) );
	}

	public static function on_init( string $arg ): void {}

	public static function filter_title( $title ) {
		return $title;
	}

	public static function on_loaded( string $arg ): void {}

	public static function filter_content( $content ) {
		return $content;
	}
}
