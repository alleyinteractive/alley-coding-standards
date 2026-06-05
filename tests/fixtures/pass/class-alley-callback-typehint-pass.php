<?php
/**
 * Class method callback typehint pass fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

class Alley_Callback_Typehint_Pass {

	public function setup() {
		// Array callback — method has no typehints — must not be flagged.
		add_action( 'init', [ $this, 'on_init' ] );

		// First-class callable — method has no typehints — must not be flagged.
		add_filter( 'the_content', $this->filter_content( ... ) );
	}

	public function on_init( $arg ) {}

	public function filter_content( $content ) {
		return $content;
	}
}
