<?php
/**
 * Class method callback typehint pass fixture.
 *
 * @package Alley\WP\Coding_Standards
 */

class Alley_Callback_Typehint_Pass {

	public function setup() {
		// Array callback to add_action — method has typehints — must not be flagged (actions not checked).
		add_action( 'init', [ $this, 'on_init' ] );

		// First-class callable — method has no typehints — must not be flagged.
		add_filter( 'the_content', $this->filter_content( ... ) );
	}

	public function on_init( string $arg ): void {}

	public function filter_content( $content ) {
		return $content;
	}
}
