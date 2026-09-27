<?php
/**
 * Block pm/cta-image (Photo call to action, D12).
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/sections/cta-image',
	'',
	array(
		'eyebrow' => get_field( 'ctai_eyebrow' ),
		'title'   => get_field( 'ctai_title' ),
		'image'   => (int) get_field( 'ctai_image' ),
		'colour'  => 'none' === get_field( 'ctai_filter' ),
		'bright'  => get_field( 'ctai_brightness' ),
		'size'    => 'standard' === get_field( 'ctai_size' ) ? 'standard' : 'tall',
		'buttons' => array(
			array( get_field( 'ctai_label' ), get_field( 'ctai_link' ) ),
			array( get_field( 'ctai_label_2' ), get_field( 'ctai_link_2' ) ),
		),
	),
	$is_preview,
	__( 'Photo call to action: add a heading in the sidebar.', 'panmotors' )
);
