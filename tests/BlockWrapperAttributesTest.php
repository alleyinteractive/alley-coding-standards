<?php
/**
 * BlockWrapperAttributesTest class file
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
 *
 * @package Alley\WP\Coding_Standards
 */

namespace Alley\WP\Coding_Standards\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Checks that every case in the Alley.PHP.BlockWrapperAttributes fail fixtures is reported.
 *
 * FixtureTest only checks which error codes appear in a file, so one case in a
 * file with many could stop being reported without a failure. Every message
 * from this sniff names the line of the get_block_wrapper_attributes() call it
 * relates to, so each call line in a fixture must appear in at least one.
 */
class BlockWrapperAttributesTest extends TestCase {
	use PhpcsHelper;

	/**
	 * Test that every get_block_wrapper_attributes() call in a fail fixture is reported.
	 *
	 * @param string $file The fixture file.
	 */
	#[DataProvider( 'fixture_data_provider' )]
	public function test_every_call_is_reported( string $file ): void {
		$call_lines = [];

		foreach ( file( $file ) as $index => $line ) {
			$code = ltrim( $line );

			// Skip comments, which mention the function by name.
			if ( str_starts_with( $code, '*' ) || str_starts_with( $code, '/' ) ) {
				continue;
			}

			if ( preg_match( '/\bget_block_wrapper_attributes\s*\(/i', $code ) ) {
				$call_lines[] = $index + 1;
			}
		}

		$this->assertNotEmpty( $call_lines, 'No get_block_wrapper_attributes() calls found.' );

		$reported_lines = [];

		foreach ( $this->run_phpcs( $file )['files'] as $data ) {
			foreach ( $data['messages'] as $message ) {
				if ( str_starts_with( $message['source'], 'Alley.PHP.BlockWrapperAttributes.' )
					&& preg_match( '/get_block_wrapper_attributes\(\) \(line (\d+)\)/', $message['message'], $matches )
				) {
					$reported_lines[] = (int) $matches[1];
				}
			}
		}

		$this->assertSame(
			[],
			array_values( array_diff( $call_lines, $reported_lines ) ),
			sprintf( 'get_block_wrapper_attributes() calls with no error in %s', basename( $file ) ),
		);
	}

	/**
	 * Returns the fail fixtures for this sniff.
	 *
	 * @return array<string, array<string>>
	 */
	public static function fixture_data_provider(): array {
		$data = [];

		foreach ( glob( __DIR__ . '/fixtures/fail/block-wrapper-attributes-*.php' ) as $file ) {
			$data[ basename( $file ) ] = [ $file ];
		}

		return $data;
	}
}
