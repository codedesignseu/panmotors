<?php
/**
 * Migration (TASKS 4d, 6 Oct 2026): the Events page and its menu items.
 *
 * - Creates the Events page (/events/) as block markup (page header, events list, CTA band) when
 *   there is no page with that address yet. An existing page is left alone.
 * - Sets Pan Motors settings → Events → Events page, when empty.
 * - Adds Events to the Primary menu just before Showroom (after Latest Cars, which stays where it
 *   is) and to the Footer menu just before Showroom, when the page is not in them yet.
 * Nothing else is changed. The demo events themselves come from the seed (created only if missing).
 *
 * Run (after committing acf-json and running the seed, which imports the new field groups):
 *   wp eval-file dev/migrations/2026-10-06-events.php --user=1
 *
 * @package panmotors
 */

require_once dirname( __DIR__ ) . '/lib.php';

$pm_id = basename( __FILE__, '.php' );
if ( ! panmotors_migration_begin( $pm_id ) ) {
	return;
}

$pm_page = panmotors_dev_page( 'events' );
if ( $pm_page ) {
	WP_CLI::log( "Events page exists (page {$pm_page}): left as it is." );
} else {
	$pm_page = (int) wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Events',
			'post_name'    => 'events',
			'post_content' => wp_slash( panmotors_demo_events_page_content() ),
		)
	);
	update_post_meta( $pm_page, '_wp_page_template', 'default' );
	update_post_meta( $pm_page, '_pm_demo', 1 );
	panmotors_seed_created( 'page:events', true );
	WP_CLI::log( "Events page created (page {$pm_page})." );
}

if ( ! get_option( 'options_events_page' ) ) {
	update_field( 'field_pm_events_page', $pm_page, 'option' );
	WP_CLI::log( 'Settings → Events → Events page set.' );
}

$pm_locations = get_nav_menu_locations();
$pm_showroom  = panmotors_dev_page( 'showroom' );
foreach ( array( 'primary', 'footer' ) as $pm_location ) {
	if ( empty( $pm_locations[ $pm_location ] ) ) {
		WP_CLI::log( "No menu in the {$pm_location} location: nothing added." );
		continue;
	}
	$pm_added = panmotors_dev_menu_add( (int) $pm_locations[ $pm_location ], $pm_page, 'Events', $pm_showroom );
	WP_CLI::log( $pm_added ? "Events added to the {$pm_location} menu." : "The {$pm_location} menu already has Events." );
}

panmotors_migration_done( $pm_id );
