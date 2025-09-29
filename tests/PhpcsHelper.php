<?php
/**
 * PhpcsHelper trait file
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
 *
 * @package Alley\WP\Coding_Standards
 */

namespace Alley\WP\Coding_Standards\Tests;

/**
 * Fixture Test
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait PhpcsHelper {
	/**
	 * Run PHPCS on a file and get the JSON output.
	 *
	 * @param string $file The file to run PHPCS on.
	 * @return array<mixed>
	 */
	protected function run_phpcs( string $file ): array {
		$this->assertFileExists( $file );

		$base_path = dirname( __DIR__ );
		$shell     = sprintf(
			'%s %s "%s" --standard=%s --no-cache --report=json',
			PHP_BINARY,
			"{$base_path}/vendor/bin/phpcs",
			$file,
			"{$base_path}/Alley-Interactive/ruleset.xml"
		);

		$output = json_decode( shell_exec( $shell ), true );

		if ( ! is_array( $output ) ) {
			$this->fail( 'Failed to run PHPCS' );
		}

		return $output;
	}

	/**
	 * Process the PHPCS output for a file.
	 *
	 * @param array<mixed>  $output The PHPCS output.
	 * @param array<string> $ignored_errors The errors to ignore. If an error is found in this list, it will be ignored and not fail the test if it is not found.
	 * @param array<string> $expected_errors The errors that are expected to be found. If an error is not found that is in this list, the test will fail.
	 */
	protected function process_phpcs_output(
		array $output,
		array $ignored_errors = [],
		array $expected_errors = [],
		bool $expect_to_fail = false,
	): void {
		// Add expected errors to the list of ignored errors.
		$ignored_errors = array_unique( array_merge( $ignored_errors, $expected_errors ) );

		$expected_errors_not_found = $expected_errors;

		foreach ( $output['files'] as $file => $data ) {
			$errors = array_column( $data['messages'], 'source' );

			// Remove any expected errors from the list.
			$unexpected_messages = array_filter(
				$data['messages'],
				fn ( $message ) => ! in_array( $message['source'], $ignored_errors, true ),
			);

			// Check if the expected errors were found and if so, remove them from the list.
			$expected_errors_not_found = array_filter(
				$expected_errors_not_found,
				fn ( $error ) => ! in_array( $error, $errors, true ),
			);

			if ( ! empty( $unexpected_messages ) ) {
				$this->fail(
					sprintf(
						'Unexpected errors found in %s: %s',
						substr( $file, strlen( __DIR__ ) ),
						print_r( $unexpected_messages, true ),
					),
				);
			} else {
				$this->assertEmpty( $unexpected_messages );
			}
		}

		$this->assertEmpty(
			$expected_errors_not_found,
			sprintf(
				'Expected errors not found in %s: %s',
				substr( $file, strlen( __DIR__ ) ),
				print_r( $expected_errors_not_found, true ),
			),
		);

		if ( $expect_to_fail && empty( $errors ) ) {
			$this->fail( 'Test did not fail as expected' );
		} else if ( $expect_to_fail && ! empty( $errors ) ) {
			$this->assertNotEmpty( $errors );
		} else {
			$this->assertEmpty( $errors );
		}
	}
}
