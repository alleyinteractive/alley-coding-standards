<?php
/**
 * DOM Attributes test file.
 *
 * Ensure that DOMDocument/DOMElement/DOMNode attributes that are camelCase'd
 * are not flagged by PHPCS.
 *
 * @package Alley\WP\Coding_Standards
 */

$alley_document = new DOMDocument();

// Ensure that DOMDocument properties are allowed.
$alley_document->preserveWhiteSpace = false;
$alley_document->formatOutput       = true;

$alley_document->loadHTML(
	file_get_contents( 'https://www.alley.com' ) // phpcs:ignore
);

// Interact with the DOM document.
$alley_element = $alley_document->getElementById( 'element-id' );

// Ensure that DOMElement properties are allowed.
$alley_element->textContent = 'Hello, world!';
$alley_element->className   = 'element-class';

$alley_tag = $alley_element->tagName;
