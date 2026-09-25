<?php
/**
 * Block pm/hero (Top video). Data from the block fields, markup in the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_video = (int) get_field( 'hero_video' );

panmotors_render_block(
	'template-parts/front/hero',
	'hero-video',
	array(
		'eyebrow'   => get_field( 'hero_eyebrow' ),
		'title'     => get_field( 'hero_title' ),
		'poster'    => (int) get_field( 'hero_poster' ),
		'video_url' => $panmotors_video ? wp_get_attachment_url( $panmotors_video ) : '',
		'cta_label' => get_field( 'hero_cta_label' ),
		'cta_link'  => get_field( 'hero_cta_link' ),
	),
	$is_preview,
	__( 'Top video: add the big title and the background photo in the sidebar.', 'panmotors' )
);
