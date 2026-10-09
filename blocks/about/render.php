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
$panmotors_video    = panmotors_block_video( 'about', $is_preview );

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
