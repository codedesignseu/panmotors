<?php
/**
 * Block pm/marquee (Marques strip). Data from the block fields, markup in the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/front/marquee',
	'',
	array(),
	$is_preview,
	__( 'Marques strip: add marques in Pan Motors settings → Marques.', 'panmotors' )
);
