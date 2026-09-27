<?php
/**
 * Block pm/story (Story, D12). The first paragraph falls back to the one-sentence description in
 * Pan Motors settings, so the business is described the same way everywhere (GEO).
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_lead = trim( (string) get_field( 'story_lead' ) );

panmotors_render_block(
	'template-parts/sections/story',
	'',
	array(
		'eyebrow' => get_field( 'story_eyebrow' ),
		'title'   => get_field( 'story_title' ),
		'lead'    => $panmotors_lead ? $panmotors_lead : panmotors_option( 'description', '' ),
		'text'    => get_field( 'story_text' ),
		'image'   => (int) get_field( 'story_image' ),
	),
	$is_preview,
	__( 'Story: add a heading and a photo in the sidebar.', 'panmotors' )
);
