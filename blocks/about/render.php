<?php
/**
 * Block pm/about (About Pan Motors). Data from the block fields, markup in the section view.
 *
 * Media type Image: the photo. Video: the poster photo, plus an uploaded clip or a YouTube / Vimeo
 * player that loads on request (about-video.js). The editor preview shows the poster only.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_is_video = 'video' === get_field( 'about_media' );
$panmotors_video    = array();
if ( $panmotors_is_video && ! $is_preview ) {
	if ( 'link' === get_field( 'about_video_source' ) ) {
		$panmotors_embed = panmotors_video_embed( (string) get_field( 'about_video_url' ) );
		if ( $panmotors_embed ) {
			$panmotors_video = array(
				'kind' => 'embed',
				'src'  => $panmotors_embed['src'],
			);
		}
	} else {
		$panmotors_file = (int) get_field( 'about_video_file' );
		$panmotors_url  = $panmotors_file ? wp_get_attachment_url( $panmotors_file ) : '';
		if ( $panmotors_url ) {
			$panmotors_video = array(
				'kind' => 'upload',
				'src'  => $panmotors_url,
				'type' => (string) get_post_mime_type( $panmotors_file ),
			);
		}
	}
}

panmotors_render_block(
	'template-parts/sections/about',
	get_field( 'about_fade' ) ? 'heritage-fade' : '',
	array(
		'image'   => (int) get_field( $panmotors_is_video ? 'about_poster' : 'about_image' ),
		'video'   => $panmotors_video,
		'eyebrow' => get_field( 'about_eyebrow' ),
		'title'   => get_field( 'about_title' ),
		'text'    => panmotors_option( 'description' ),
		'stats'   => panmotors_rows( 'about_stats' ),
		'fade'    => (bool) get_field( 'about_fade' ),
	),
	$is_preview
);

if ( $panmotors_video ) {
	panmotors_use_module( 'about-video' );
}
