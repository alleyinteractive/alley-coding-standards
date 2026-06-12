<?php
/**
 * Class method filter callback typehint fail fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

class Alley_Callback_Typehint_Fail {

	public function setup() {
		// First-class callable — method has parameter typehint and return type.
		add_filter( 'the_content', $this->filter_content( ... ) );
	}

	public function filter_content( string $content ): string {
		return $content;
	}
}
