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
	 * Returns an array of fixtures that should pass.
	 *
	 * @return array<string>
	 */
	public static function passing_fixture_data_provider() {
		return array_map( fn ( $file ) => [ $file ], self::get_files_in_directory( __DIR__ . '/fixtures/pass' ) );
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

		return glob( $directory . '/*' );
	}
}
