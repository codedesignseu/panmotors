<?php
/**
 * Migration (D11, 25 Sep 2026): remove the data of the per-page model that nothing reads any more.
 * Was part of the seed until 27 Sep 2026.
 *
 * - Post meta of the old per-page and Home field groups on the section pages and Home (these keys
 *   only).
 * - The page mapping options (page_featured, page_about, ...), replaced by blocks.
 *
 * Run: wp eval-file dev/migrations/2026-09-25-d11-old-model-cleanup.php --user=1
 *
 * @package panmotors
 */

require_once dirname( __DIR__ ) . '/lib.php';

$pm_id = basename( __FILE__, '.php' );
if ( ! panmotors_migration_begin( $pm_id ) ) {
	return;
}

$pm_old_keys = array( 'page_eyebrow', 'page_intro', 'page_hero_image', 'page_body', 'featured_cars', 'about_image', 'about_story', 'about_stats', 'values', 'latest_cars', 'showroom_photos', 'getting_here', 'enquire_title', 'enquire_intro', 'faq_title', 'faqs', 'hero_', 'live_', 'home_', 'form_' );
$pm_pages    = array_filter( array_merge( array_map( 'panmotors_dev_page', array( 'featured-cars', 'about', 'latest-cars', 'showroom', 'contact' ) ), array( (int) get_option( 'page_on_front' ) ) ) );
$pm_removed  = 0;
foreach ( $pm_pages as $pm_page_id ) {
	foreach ( array_keys( get_post_meta( $pm_page_id ) ) as $pm_key ) {
		$pm_bare = ltrim( $pm_key, '_' );
		foreach ( $pm_old_keys as $pm_prefix ) {
			if ( $pm_bare === $pm_prefix || str_starts_with( $pm_bare, rtrim( $pm_prefix, '_' ) . '_' ) || ( str_ends_with( $pm_prefix, '_' ) && str_starts_with( $pm_bare, $pm_prefix ) ) ) {
				delete_post_meta( $pm_page_id, $pm_key );
				++$pm_removed;
				break;
			}
		}
	}
}
foreach ( array( 'page_featured', 'page_about', 'page_latest', 'page_showroom', 'page_values', 'page_live' ) as $pm_gone ) {
	delete_option( "options_{$pm_gone}" );
	delete_option( "_options_{$pm_gone}" );
}
WP_CLI::log( "Old page fields removed: {$pm_removed}." );

panmotors_migration_done( $pm_id );
