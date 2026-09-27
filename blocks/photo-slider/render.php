<?php
/**
 * Block pm/photo-slider (Photo slider, D12). Photos from the block's gallery, captions from the
 * media library.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/sections/photo-slider',
	'photo-slider',
	array(
		'title'  => get_field( 'slider_title' ),
		'hint'   => get_field( 'slider_hint' ),
		'photos' => panmotors_rows( 'slider_photos' ),
	),
	$is_preview,
	__( 'Photo slider: add photos in the sidebar.', 'panmotors' )
);
