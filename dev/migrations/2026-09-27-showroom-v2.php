<?php
/**
 * Migration (D12 step 5, 27 Sep 2026): the Showroom page gets the v2 layout
 * (_design/v2/showroom.html): page header (photo), photo slider "Inside", visit (hours and address
 * from Pan Motors settings), photo call to action.
 *
 * Changes only that page's content, and only while it still has the seed's earlier layout (the Page
 * top block, no Page header). A page someone has rebuilt is left alone. The previous content stays
 * in the page's revisions.
 *
 * Run: wp eval-file dev/migrations/2026-09-27-showroom-v2.php --user=1   (after committing acf-json)
 *
 * @package panmotors
 */

require_once dirname( __DIR__ ) . '/lib.php';

$pm_id = basename( __FILE__, '.php' );
if ( ! panmotors_migration_begin( $pm_id ) ) {
	return;
}

$pm_page = panmotors_dev_page( 'showroom' );
$pm_old  = $pm_page ? (string) get_post_field( 'post_content', $pm_page ) : '';

if ( ! $pm_page ) {
	WP_CLI::log( 'No Showroom page: nothing to change (the seed creates it with the new layout).' );
} elseif ( false === strpos( $pm_old, '<!-- wp:pm/page-hero' ) || false !== strpos( $pm_old, '<!-- wp:pm/page-header' ) ) {
	WP_CLI::warning( 'Showroom no longer has the earlier layout: left as it is.' );
} else {
	wp_update_post(
		array(
			'ID'           => $pm_page,
			'post_content' => wp_slash( panmotors_demo_showroom_content() ),
		)
	);
	WP_CLI::log( "Showroom (page {$pm_page}): v2 layout." );
}

panmotors_migration_done( $pm_id );
