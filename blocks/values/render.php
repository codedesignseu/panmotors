<?php
/**
 * Block pm/values (Our Values). Data from the block fields, markup in the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/sections/values',
	'',
	array(
		'title'  => get_field( 'values_title' ),
		'url'    => get_field( 'values_link' ),
		'values' => panmotors_rows( 'values' ),
	),
	$is_preview,
	__( 'Our Values: add values in the sidebar.', 'panmotors' )
);
