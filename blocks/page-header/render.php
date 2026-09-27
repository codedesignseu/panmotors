<?php
/**
 * Block pm/page-header (Page header, D12). The page's H1: Photo or Text style.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_title = trim( (string) get_field( 'header_title' ) );

panmotors_render_block(
	'template-parts/sections/page-header',
	'',
	array(
		'style'      => 'image' === get_field( 'header_style' ) ? 'image' : 'text',
		'eyebrow'    => get_field( 'header_eyebrow' ),
		'title'      => $panmotors_title ? $panmotors_title : get_the_title( (int) $post_id ),
		'intro'      => get_field( 'header_intro' ),
		'image'      => (int) get_field( 'header_image' ),
		'grayscale'  => 'grayscale' === get_field( 'header_filter' ),
		'brightness' => (int) get_field( 'header_brightness' ) ? (int) get_field( 'header_brightness' ) : 62, // Emptied field: the default.
	),
	$is_preview
);
