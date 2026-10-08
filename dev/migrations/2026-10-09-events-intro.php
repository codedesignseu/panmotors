<?php
/**
 * Migration (9 Oct 2026): Events intro on Home (_design/home-events/events-intro.html).
 *
 * Adds the pm/events-intro block to the homepage just before Pan Motors Live (after Latest Cars
 * when Home has no Live block), with the field defaults; its button then goes to Settings →
 * Events → Events page. Nothing happens when Home already has the block or has neither block (add
 * it in the editor instead). Nothing else is changed. Demo events come from the seed.
 *
 * Run (after committing acf-json and running the seed, which imports the new field group):
 *   wp eval-file dev/migrations/2026-10-09-events-intro.php --user=1
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
$pm_live   = array_search( 'pm/live', $pm_names, true );
$pm_latest = array_search( 'pm/latest-cars', $pm_names, true );
$pm_at     = false !== $pm_live ? $pm_live : ( false !== $pm_latest ? $pm_latest + 1 : false );

if ( ! $pm_front ) {
	WP_CLI::log( 'No homepage set: nothing changed.' );
} elseif ( in_array( 'pm/events-intro', $pm_names, true ) ) {
	WP_CLI::log( 'Home already has Events intro: left as it is.' );
} elseif ( false === $pm_at ) {
	WP_CLI::log( 'Home has neither Pan Motors Live nor Latest Cars: nothing changed (add Events intro in the editor).' );
} else {
	array_splice( $pm_blocks, $pm_at, 0, parse_blocks( panmotors_demo_events_intro_block() . "\n\n" ) );
	wp_update_post(
		array(
			'ID'           => $pm_front,
			'post_content' => wp_slash( serialize_blocks( $pm_blocks ) ),
		)
	);
	WP_CLI::log( 'Home: Events intro added ' . ( false !== $pm_live ? 'before Pan Motors Live.' : 'after Latest Cars.' ) );
}

panmotors_migration_done( $pm_id );
