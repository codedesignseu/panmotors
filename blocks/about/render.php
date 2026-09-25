<?php
/**
 * Block pm/about (About Pan Motors). Data from the block fields, markup in the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/sections/about',
	get_field( 'about_fade' ) ? 'heritage-fade' : '',
	array(
		'image'   => (int) get_field( 'about_image' ),
		'eyebrow' => get_field( 'about_eyebrow' ),
		'title'   => get_field( 'about_title' ),
		'text'    => panmotors_option( 'description' ),
		'stats'   => panmotors_rows( 'about_stats' ),
		'fade'    => (bool) get_field( 'about_fade' ),
	),
	$is_preview
);
