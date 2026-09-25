<?php
/**
 * Block pm/page-hero (Page top). Data from the block fields, markup in the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/components/page-hero',
	'',
	array(
		'page_id' => (int) $post_id,
		'eyebrow' => get_field( 'page_eyebrow' ),
		'intro'   => get_field( 'page_intro' ),
		'image'   => (int) get_field( 'page_hero_image' ),
	),
	$is_preview
);
