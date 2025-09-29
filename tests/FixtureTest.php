<?php
/**
 * FixtureTest class file
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
 *
 * @package Alley\WP\Coding_Standards
 */

namespace Alley\WP\Coding_Standards\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Fixture Test
 */
class FixtureTest extends TestCase {
	use PhpcsHelper;

	/**
	 * Test that fixtures pass.
	 *
	 * @param string $file The file to test.
	 */
	#[DataProvider( 'passing_fixture_data_provider' )]
	public function test_passing_fixtures( string $file ): void {
		$this->process_phpcs_output( $this->run_phpcs( $file ) );
	}

	/**
	 * Test that fixtures fail.
	 *
	 * @param string $file The file to test.
	 * @param string $expectation The expectations file for this test.
	 */
	#[DataProvider( 'failing_fixture_data_provider' )]
	public function test_failing_fixtures( string $file, ?string $expectation = null ): void {

		$expectations = [];

		if ( ! empty( $expectation ) && file_exists( $expectation ) ) {
			$expectations = include $expectation;
		}

		$this->process_phpcs_output(
			$this->run_phpcs( $file ),
			ignored_errors: $expectations,
			expect_to_fail: true
		);
	}

	/**
	 * Returns an array of fixtures that should pass.
	 *
	 * @return array<string>
	 */
	public static function passing_fixture_data_provider(): array {
		return self::get_files_in_directory( __DIR__ . '/fixtures/pass' );
	}

	/**
	 * Returns an array of fixtures that should fail.
	 *
	 * @return array<array<string|array<string>>>
	 */
	public static function failing_fixture_data_provider(): array {
		$data         = self::get_files_in_directory( __DIR__ . '/fixtures/fail' );
		$expectations = self::get_files_in_directory( __DIR__ . '/fixtures/fail/expectations' );

		foreach ( $data as $key => $data_set ) {
			if ( ! isset( $expectations[ $key ] ) ) {
				$data[ $key ][] = null;
				continue;
			}

			$data[ $key ][] = $expectations[ $key ][0];
		}

		return $data;
	}

	/**
	 * Returns an array of fixtures that should fail.
	 *
	 * @param string $directory The directory to get files from.
	 * @return array<string>
	 */
	protected static function get_files_in_directory( string $directory ): array {
		if ( ! is_dir( $directory ) ) {
			return [];
		}

		$files = glob( $directory . '/*' );

		if ( ! is_array( $files ) ) {
			return [];
		}

		$data = [];

		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) {
				continue;
			}

			$data[ basename( $file ) ] = [ $file ];
		}

		return $data;
	}
}
