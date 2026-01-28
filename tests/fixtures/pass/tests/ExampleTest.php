<?php

/**
 * Example test file
 *
 * @package Alley\WP\Coding_Standards
 */

class ExampleTest {
	public function test_example(): void {
		echo $unknown_variable;
		echo get_blog_info( 'name' );

		// Perform a meta query.
		$query = new WP_Query(
			[
				'post_type'  => 'post',
				'meta_query' => [
					[
						'key'     => 'example_key',
						'value'   => 'example_value',
						'compare' => '=',
					],
				],
			]
		);

		// Perform a tax query.
		$query = new WP_Query(
			[
				'post_type' => 'post',
				'tax_query' => [
					[
						'taxonomy' => 'category',
						'field'    => 'slug',
						'terms'    => 'example-category',
					],
				],
			]
		);
	}
}
