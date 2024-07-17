<?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
/**
 * FixtureTest class file
 *
 * @package Alley\WP\Coding_Standards
 */

namespace Alley\WP\Coding_Standards;

use PHP_CodeSniffer\Runner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Fixture Test
 */
class FixtureTest extends TestCase {
	/**
	 * The original argv.
	 *
	 * @var array<string>
	 */
	protected static array $argv;

	/**
	 * Set up before class.
	 */
	public static function setUpBeforeClass(): void {
		// Store argv for restoring after we're done.
		static::$argv = $_SERVER['argv']; // phpcs:ignore

		// Ensure PHPCS is loaded.
		$reflection = new \ReflectionClass( \Composer\Autoload\ClassLoader::class );
		$vendor_dir = dirname( dirname( $reflection->getFileName() ) );

		require_once $vendor_dir . '/squizlabs/php_codesniffer/autoload.php';
	}

	/**
	 * Tear down after class.
	 */
	public static function tearDownAfterClass(): void {
		// Restore argv.
		$_SERVER['argv'] = static::$argv;
	}

	/**
	 * Test that fixtures pass.
	 *
	 * @param string $file The file to test.
	 */
	#[DataProvider( 'passing_fixture_data_provider' )]
	public function test_passing_fixtures( string $file ): void {
		$this->assertFileExists( $file );

		$base_path = dirname( __DIR__ );

		$_SERVER['argv'] = [
			"{$base_path}/vendor/bin/phpcs",
			$file,
			'-vsn',
			'--no-cache',
			"--standard={$base_path}/Alley-Interactive/ruleset.xml",
		];

		ob_start();
		$exit_code = ( new Runner() )->runPHPCS();
		$output    = ob_get_clean();

		$this->assertSame( 0, $exit_code, $output );
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
