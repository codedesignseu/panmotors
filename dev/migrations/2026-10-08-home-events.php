<?php
/**
 * Migration (8 Oct 2026): Upcoming events on Home.
 *
 * Adds the pm/upcoming-events block to the homepage right after Latest Cars (before Pan Motors
 * Live), with its button to the Events page. Nothing happens when Home already has the block or has
 * no Latest Cars block (the client moved things around: add it in the editor instead). Nothing else
 * is changed.
 *
 * Run (after committing acf-json and running the seed, which imports the new field group):
 *   wp eval-file dev/migrations/2026-10-08-home-events.php --user=1
 *
 * @package panmotors
 */

require_once dirname( __DIR__ ) . '/lib.php';

$pm_id = basename( __FILE__, '.php' );
if ( ! panmotors_migration_begin( $pm_id ) ) {
	return;
}

$pm_front  = (int) get_option( 'page_on_front' );
$pm_blocks = $pm_front ? parse_blocks( get_post_field( 'post_content', $pm_front ) ) : array();
$pm_names  = array_column( $pm_blocks, 'blockName' );
$pm_after  = array_search( 'pm/latest-cars', $pm_names, true );

if ( ! $pm_front ) {
	WP_CLI::log( 'No homepage set: nothing changed.' );
} elseif ( in_array( 'pm/upcoming-events', $pm_names, true ) ) {
	WP_CLI::log( 'Home already has Upcoming events: left as it is.' );
} elseif ( false === $pm_after ) {
	WP_CLI::log( 'Home has no Latest Cars block: nothing changed (add Upcoming events in the editor).' );
} else {
	$pm_new = parse_blocks( "\n\n" . panmotors_demo_upcoming_events_block( panmotors_dev_page( 'events' ) ) );
	array_splice( $pm_blocks, $pm_after + 1, 0, $pm_new );
	wp_update_post(
		array(
			'ID'           => $pm_front,
			'post_content' => wp_slash( serialize_blocks( $pm_blocks ) ),
		)
	);
	WP_CLI::log( 'Home: Upcoming events added after Latest Cars.' );
}

panmotors_migration_done( $pm_id );
