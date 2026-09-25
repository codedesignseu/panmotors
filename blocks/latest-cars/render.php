<?php
/**
 * Block pm/latest-cars (Latest Cars). Data from the block fields, markup in the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/sections/latest-cars',
	'slider-drag',
	array(
		'eyebrow' => get_field( 'latest_eyebrow' ),
		'title'   => get_field( 'latest_title' ),
		'slides'  => panmotors_cars(
			array(
				'mode'  => 'latest',
				'limit' => (int) get_field( 'latest_limit' ),
			)
		),
	),
	$is_preview,
	__( 'Latest Cars: add cars under Cars.', 'panmotors' )
);
