<?php
/**
 * Block pm/cars-grid (Cars grid, D12). The cars come from the Cars list (pm_car): every car with
 * "On the Featured Cars page" on, or the cars marked Featured.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_labels = array();
foreach ( array( 'year', 'engine', 'power', 'sprint', 'gearbox', 'colour', 'mileage', 'no' ) as $panmotors_key ) {
	$panmotors_labels[ $panmotors_key ] = (string) get_field( 'cars_label_' . $panmotors_key );
}

panmotors_render_block(
	'template-parts/sections/cars-grid',
	'cars-grid',
	array(
		'cars'    => panmotors_cars(
			array(
				'mode'  => 'featured' === get_field( 'cars_source' ) ? 'featured' : 'page',
				'limit' => 100,
			)
		),
		'intro'   => get_field( 'cars_intro' ),
		'all'     => get_field( 'cars_all_label' ),
		'enquire' => get_field( 'cars_enquire_label' ),
		'link'    => get_field( 'cars_enquire_link' ),
		'labels'  => $panmotors_labels,
	),
	$is_preview,
	__( 'Cars grid: no cars to show. Add cars under Cars, with "On the Featured Cars page" on.', 'panmotors' )
);
