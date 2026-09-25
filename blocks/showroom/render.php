<?php
/**
 * Block pm/showroom (The Showroom). Data from the block fields, markup in the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/sections/showroom',
	'showroom-slider',
	array(
		'eyebrow' => get_field( 'showroom_eyebrow' ),
		'title'   => get_field( 'showroom_title' ),
		'intro'   => get_field( 'showroom_intro' ),
		'photos'  => panmotors_rows( 'showroom_photos' ),
		'hours'   => (bool) get_field( 'showroom_hours' ),
	),
	$is_preview,
	__( 'The Showroom: add photos in the sidebar.', 'panmotors' )
);
