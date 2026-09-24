<?php
/**
 * Site header.
 *
 * Minimal shell for section 1. Nav, logo, menu, contact pill, burger and
 * mobile menu are built in section 3.
 *
 * @package panmotors
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="pm-skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'panmotors' ); ?></a>
