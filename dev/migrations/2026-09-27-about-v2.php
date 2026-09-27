<?php
/**
 * Migration (D12 step 3, 27 Sep 2026): the About page gets the v2 layout (_design/v2/about.html):
 * page header (photo), story, What We Do, Our Values (light section), photo call to action.
 *
 * Changes only the About page's content, and only while it still has the seed's earlier layout
 * (the Page top block, no Page header). An About page someone has already rebuilt is left alone.
 * The previous content stays in the page's revisions.
 *
 * Run: wp eval-file dev/migrations/2026-09-27-about-v2.php --user=1   (after committing acf-json)
 *
 * @package panmotors
 */

require_once dirname( __DIR__ ) . '/lib.php';

$pm_id = basename( __FILE__, '.php' );
if ( ! panmotors_migration_begin( $pm_id ) ) {
	return;
}

$pm_about = panmotors_dev_page( 'about' );
$pm_old   = $pm_about ? (string) get_post_field( 'post_content', $pm_about ) : '';

if ( ! $pm_about ) {
	WP_CLI::log( 'No About page: nothing to change (the seed creates it with the new layout).' );
} elseif ( false === strpos( $pm_old, '<!-- wp:pm/page-hero' ) || false !== strpos( $pm_old, '<!-- wp:pm/page-header' ) ) {
	WP_CLI::warning( 'About no longer has the earlier layout: left as it is. Build it from the blocks by hand if needed.' );
} else {
	wp_update_post(
		array(
			'ID'           => $pm_about,
			'post_content' => wp_slash( panmotors_demo_about_content() ),
		)
	);
	WP_CLI::log( "About (page {$pm_about}): v2 layout." );
}

panmotors_migration_done( $pm_id );
