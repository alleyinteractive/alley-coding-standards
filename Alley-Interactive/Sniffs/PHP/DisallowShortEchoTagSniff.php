<?php
/**
 * DisallowShortEchoTagSniff class file
 *
 * @package Alley\CodingStandards
 */

namespace Alley\Sniffs\PHP;

use Alley\CodingStandards\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Due to a limitation in PHPCS's DisallowShortOpenTagSniff sniff which will
 * allow the use of PHP's short echo tag `<?=` if `short_open_tag` is enabled,
 * we need to create a new sniff to disallow the short echo tag at all times.
 *
 * This rule exists as `Alley.PHP.DisallowShortEchoTag.Found` and can be ignored
 * in a file by adding `// phpcs:ignore Alley.PHP.DisallowShortEchoTag.Found`.
 *
 * @link https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/08a864f644e026d1e4ca446b59304ee19e41c8ea/src/Standards/Generic/Sniffs/PHP/DisallowShortOpenTagSniff.php#L33-L35
 */
class DisallowShortEchoTagSniff extends Sniff {
	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return [ T_OPEN_TAG_WITH_ECHO ]; // This detects `<?=`
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * Any detected short echo tags will be flagged as an error.
	 *
	 * @param File $file The file being scanned.
	 * @param int  $stack_ptr The position of the current token in the stack passed in $tokens.
	 */
	public function process_token( $stackPtr ) {
		$error = 'Short echo tag (<?=) is not allowed. Use full PHP tags with echo instead.';
		$this->phpcsFile->addError( $error, $stackPtr, 'Found' );
	}
}
