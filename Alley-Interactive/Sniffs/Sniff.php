<?php
/**
 * Alley Interactive Coding Standard.
 *
 * @package Alley\CodingStandards
 * @link https://github.com/alleyinteractive/alley-coding-standards
 */

namespace Alley\CodingStandards\Sniffs;

use WordPressCS\WordPress\Sniff as WPCS_Sniff;

/**
 * Represents a WordPress\Sniff for sniffing VIP coding standards.
 *
 * Provides a bootstrap for the sniffs, to reduce code duplication.
 */
abstract class Sniff extends WPCS_Sniff {
}
