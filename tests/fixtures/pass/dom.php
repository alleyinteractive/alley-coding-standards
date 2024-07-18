<?php
/**
 * DOM Attributes test file.
 *
 * Ensure that DOMDocument/DOMElement/DOMNode attributes that are camelCase'd
 * are not flagged by PHPCS.
 *
 * @package Alley\WP\Coding_Standards
 */

$ai_document = new DOMDocument();

// Ensure that DOMDocument properties are allowed.
$ai_document->preserveWhiteSpace = false;
$ai_document->formatOutput       = true;

$ai_document->loadHTML(
	file_get_contents( 'https://www.alley.com' ) // phpcs:ignore
);

// Interact with the DOM document.
$ai_element = $ai_document->getElementById( 'element-id' );

// Ensure that DOMElement properties are allowed.
$ai_element->textContent = 'Hello, world!';
$ai_element->className   = 'element-class';

$ai_tag = $ai_element->tagName;
