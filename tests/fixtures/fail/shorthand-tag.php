<?php
/**
 * Shorthand Tag Functions file.
 *
 * @package Alley\WP\CodingStandards
 */

?>

<div
	class="example <?= esc_attr( $class ); ?>"
	data-action="<?= esc_attr( $action ); ?>"
>
	<?= esc_html( $content ); ?>
</div>
