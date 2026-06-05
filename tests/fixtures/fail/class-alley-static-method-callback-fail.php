<?php
/**
 * Static class method callback typehint fail fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

class Alley_Static_Method_Callback_Fail {

	public function setup() {
		// String callback 'ClassName::method' — method has typehints.
		add_action( 'init', 'Alley_Static_Method_Callback_Fail::on_init' );

		// Array callback [ 'ClassName', 'method' ] — method has typehints.
		add_filter( 'the_title', [ 'Alley_Static_Method_Callback_Fail', 'filter_title' ] );

		// Array callback [ self::class, 'method' ] — method has typehints.
		add_action( 'wp_loaded', [ self::class, 'on_loaded' ] );

		// First-class callable self::method(...) — method has typehints.
		add_filter( 'the_content', self::filter_content( ... ) );
	}

	public static function on_init( string $arg ): void {}

	public static function filter_title( string $title ): string {
		return $title;
	}

	public static function on_loaded( string $arg ): void {}

	public static function filter_content( string $content ): string {
		return $content;
	}
}
