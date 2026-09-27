<?php
/**
 * Migration (D12 step 4, 27 Sep 2026): the Featured Cars page gets the v2 layout
 * (_design/v2/cars.html): page header (text), cars grid, CTA band.
 *
 * Changes only that page's content, and only while it still has the seed's earlier layout (the Page
 * top block, no Page header). A page someone has rebuilt is left alone. The previous content stays
 * in the page's revisions. Run after the car-sheets migration and the seed (which creates the cars).
 *
 * Run: wp eval-file dev/migrations/2026-09-27-featured-cars-v2.php --user=1
 *
 * @package panmotors
 */

require_once dirname( __DIR__ ) . '/lib.php';

$pm_id = basename( __FILE__, '.php' );
if ( ! panmotors_migration_begin( $pm_id ) ) {
	return;
}

$pm_page = panmotors_dev_page( 'featured-cars' );
$pm_old  = $pm_page ? (string) get_post_field( 'post_content', $pm_page ) : '';

if ( ! $pm_page ) {
	WP_CLI::log( 'No Featured Cars page: nothing to change (the seed creates it with the new layout).' );
} elseif ( false === strpos( $pm_old, '<!-- wp:pm/page-hero' ) || false !== strpos( $pm_old, '<!-- wp:pm/page-header' ) ) {
	WP_CLI::warning( 'Featured Cars no longer has the earlier layout: left as it is.' );
} else {
	wp_update_post(
		array(
			'ID'           => $pm_page,
			'post_content' => wp_slash( panmotors_demo_featured_content() ),
		)
	);
	WP_CLI::log( "Featured Cars (page {$pm_page}): v2 layout." );
}

panmotors_migration_done( $pm_id );
