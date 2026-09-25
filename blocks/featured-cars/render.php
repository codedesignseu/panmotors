<?php
/**
 * Block pm/featured-cars (Featured Cars). Data from the block fields, markup in the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_pick = 'pick' === get_field( 'featured_source' );

panmotors_render_block(
	'template-parts/sections/featured-cars',
	'',
	array(
		'title' => get_field( 'featured_title' ),
		'intro' => get_field( 'featured_intro' ),
		'cars'  => panmotors_cars(
			array(
				'mode'  => $panmotors_pick ? 'pick' : 'featured',
				'ids'   => $panmotors_pick ? panmotors_rows( 'featured_pick' ) : array(),
				'limit' => (int) get_field( 'featured_limit' ),
			)
		),
	),
	$is_preview,
	__( 'Featured Cars: no cars to show. Mark cars as Featured under Cars, or pick cars in the sidebar.', 'panmotors' )
);
