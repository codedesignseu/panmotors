<?php
/**
 * Block pm/values (Our Values). The values come from Pan Motors settings → Our Values (D12), so
 * every page shows the same ones; the block sets the style, heading, intro and link.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_light = 'light' === get_field( 'values_style' );

panmotors_render_block(
	'template-parts/sections/values',
	'',
	array(
		'style'  => $panmotors_light ? 'light' : 'dark',
		'title'  => get_field( 'values_title' ),
		'intro'  => $panmotors_light ? get_field( 'values_intro' ) : '',
		'url'    => $panmotors_light ? '' : get_field( 'values_link' ),
		'values' => panmotors_rows( 'values', 'option' ),
	),
	$is_preview,
	__( 'Our Values: add values in Pan Motors settings → Our Values.', 'panmotors' )
);
