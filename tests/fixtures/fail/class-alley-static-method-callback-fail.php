<?php
/**
 * Static class method filter callback typehint fail fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

class Alley_Static_Method_Callback_Fail {

	public function setup() {
		// Array callback [ 'ClassName', 'method' ] — method has typehints.
		add_filter( 'the_title', [ 'Alley_Static_Method_Callback_Fail', 'filter_title' ] );

		// First-class callable self::method(...) — method has typehints.
		add_filter( 'the_content', self::filter_content( ... ) );
	}

	public static function filter_title( string $title ): string {
		return $title;
	}

	public static function filter_content( string $content ): string {
		return $content;
	}
}
