<?php
/**
 * Migration (TASKS §5, 27 Sep 2026): the About page is an AboutPage for search engines
 * (Page settings → Type for search engines). Written only when the field was never saved.
 *
 * Run: wp eval-file dev/migrations/2026-09-27-about-page-type.php --user=1   (after committing acf-json)
 *
 * @package panmotors
 */

require_once dirname( __DIR__ ) . '/lib.php';

$pm_id = basename( __FILE__, '.php' );
if ( ! panmotors_migration_begin( $pm_id ) ) {
	return;
}

$pm_about = panmotors_dev_page( 'about' );
if ( $pm_about && ! metadata_exists( 'post', $pm_about, 'schema_type' ) ) {
	update_field( 'field_pm_schema_type', 'about', $pm_about );
	WP_CLI::log( "About (page {$pm_about}): type for search engines set to About page." );
} else {
	WP_CLI::log( 'About: no page, or its type is already set. Nothing to change.' );
}

panmotors_migration_done( $pm_id );
