<?php
/**
 * Migration (D12 step 4, 27 Sep 2026): the existing cars get the car sheet fields.
 *
 * - The McLaren 720S (seed key "mclaren") is car No. 03 of the v2 Featured Cars page: its year,
 *   power, acceleration, mileage, engine, gearbox, colour and note from _design/v2/cars.html, and
 *   order 3 while its order is still 0.
 * - The other five cars of the first design are not in v2: "On the Featured Cars page" off. They
 *   stay on the homepage exactly as before (Featured row and Latest Cars).
 * Only fields that were never saved are written, so values set in wp-admin stay. The eleven other
 * v2 cars are created by the seed (only when missing).
 *
 * Run: wp eval-file dev/migrations/2026-09-27-car-sheets.php --user=1   (after committing acf-json)
 *
 * @package panmotors
 */

require_once dirname( __DIR__ ) . '/lib.php';

$pm_id = basename( __FILE__, '.php' );
if ( ! panmotors_migration_begin( $pm_id ) ) {
	return;
}

$pm_values = array(
	'mclaren'        => array(
		'field_pm_car_year'    => '2021',
		'field_pm_car_power'   => '720 hp',
		'field_pm_car_sprint'  => '0–100 in 2.9 s',
		'field_pm_car_mileage' => '11,200 km',
		'field_pm_car_engine'  => '4.0 V8, twin turbo',
		'field_pm_car_gearbox' => 'SSG, 7 speed',
		'field_pm_car_colour'  => 'Silica white',
		'field_pm_car_note'    => 'Performance spec, carbon exterior pack.',
		'field_pm_car_on_page' => 1,
	),
	'red_night'      => array( 'field_pm_car_on_page' => 0 ),
	'black_studio'   => array( 'field_pm_car_on_page' => 0 ),
	'grey_sunset'    => array( 'field_pm_car_on_page' => 0 ),
	'ferrari_rear'   => array( 'field_pm_car_on_page' => 0 ),
	'porsche_studio' => array( 'field_pm_car_on_page' => 0 ),
);

foreach ( $pm_values as $pm_key => $pm_fields ) {
	$pm_car = get_posts(
		array(
			'post_type'      => 'pm_car',
			'post_status'    => 'any',
			'meta_key'       => '_pm_seed_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $pm_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! $pm_car ) {
		WP_CLI::log( "Car {$pm_key}: not found, skipped." );
		continue;
	}
	$pm_car = (int) $pm_car[0];
	$pm_set = array();
	foreach ( $pm_fields as $pm_field_key => $pm_value ) {
		$pm_field = acf_get_field( $pm_field_key );
		// The note existed before: written only while empty. The new fields: only when never saved.
		$pm_saved = get_post_meta( $pm_car, $pm_field['name'], true );
		if ( 'note' === $pm_field['name'] ? '' !== (string) $pm_saved : metadata_exists( 'post', $pm_car, $pm_field['name'] ) ) {
			continue;
		}
		update_field( $pm_field_key, $pm_value, $pm_car );
		$pm_set[] = $pm_field['name'];
	}
	if ( 'mclaren' === $pm_key && 0 === (int) get_post_field( 'menu_order', $pm_car ) ) {
		wp_update_post(
			array(
				'ID'         => $pm_car,
				'menu_order' => 3,
			)
		);
		$pm_set[] = 'order 3';
	}
	WP_CLI::log( get_the_title( $pm_car ) . ': ' . ( $pm_set ? implode( ', ', $pm_set ) : 'nothing to change' ) . '.' );
}

panmotors_migration_done( $pm_id );
