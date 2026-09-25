<?php
/**
 * Block pm/live (Pan Motors Live). Data from the block fields, markup in the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/sections/live',
	'live-videos',
	array(
		'eyebrow'   => get_field( 'live_eyebrow' ),
		'title'     => get_field( 'live_title' ),
		'cta_label' => get_field( 'live_cta_label' ),
		'posts'     => panmotors_rows( 'live_posts' ),
	),
	$is_preview,
	__( 'Pan Motors Live: add posts in the sidebar.', 'panmotors' )
);
