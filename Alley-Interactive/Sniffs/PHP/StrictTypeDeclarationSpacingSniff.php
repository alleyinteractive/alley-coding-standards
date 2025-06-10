<?php
/**
 * StrictTypeDeclarationSpacingSniff class file
 *
 * @package Alley\CodingStandards
 */

namespace Alley\Sniffs\PHP;

use WordPressCS\WordPress\Sniff;

class StrictTypeDeclarationSpacingSniff extends Sniff {
	/**
	 * Returns tokens to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return [ T_DECLARE ];
	}

	/**
	 * Processes this sniff when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 */
	public function process_token( $stackPtr ) {
		$tokens = $this->phpcsFile->getTokens();

		// Find strict_types declaration.
		$strictTypePtr = $this->phpcsFile->findNext(T_STRING, $stackPtr + 1, null, false, 'strict_types');
		if ( $strictTypePtr === false ) {
			return;
		}

		// Check for the opening parenthesis.
		$openParenPtr = $this->phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);
		if ($openParenPtr !== false) {

			// Check for space after OPENING parenthesis.
			$nextTokenPtr = $openParenPtr + 1;
			if ($tokens[$nextTokenPtr]['code'] === T_WHITESPACE) {
				$error = 'No space allowed after opening parenthesis in strict_types declaration';
				$fix   = $this->phpcsFile->addFixableError($error, $nextTokenPtr, 'SpaceAfterOpenParenthesis');
				if ($fix) {
					$this->phpcsFile->fixer->replaceToken($nextTokenPtr, '');
				}
			}

			// Check for space before ENDing parenthesis.
			$endParenPtr = $this->phpcsFile->findNext(T_CLOSE_PARENTHESIS, $nextTokenPtr + 1);
			if ($endParenPtr !== false && $tokens[$endParenPtr - 1]['code'] === T_WHITESPACE) {
				$error = 'No space allowed before closing parenthesis in strict_types declaration';
				$fix   = $this->phpcsFile->addFixableError($error, $endParenPtr - 1, 'SpaceBeforeCloseParenthesis');
				if ($fix) {
					$this->phpcsFile->fixer->replaceToken($endParenPtr - 1, '');
				}
			}
		}

		// Check for space before the = sign.
		$equalsPtr = $this->phpcsFile->findNext(T_EQUAL, $strictTypePtr + 1);
		if ($equalsPtr !== false && $tokens[$equalsPtr - 1]['code'] === T_WHITESPACE) {
			$error = 'No space allowed before equals sign in strict_types declaration';
			$fix   = $this->phpcsFile->addFixableError($error, $equalsPtr - 1, 'SpaceBeforeEquals');
			if ($fix) {
				$this->phpcsFile->fixer->replaceToken($equalsPtr - 1, '');
			}
		}

		// Check for space after the = sign.
		$nextTokenPtr = $equalsPtr + 1;
		if ($nextTokenPtr < count($tokens) && $tokens[$nextTokenPtr]['code'] === T_WHITESPACE) {
			$error = 'No space allowed after equals sign in strict_types declaration';
			$fix   = $this->phpcsFile->addFixableError($error, $nextTokenPtr, 'SpaceAfterEquals');
			if ($fix) {
				$this->phpcsFile->fixer->replaceToken($nextTokenPtr, '');
			}
		}
	}
}
