<?php
/**
 * Migration (D12 step 6, 27 Sep 2026): the Contact page gets the v2 layout
 * (_design/v2/contact.html): page header (text), contact rows, contact form and map, questions.
 *
 * The page's existing Questions block is carried over as it is (same questions and answers, the
 * DRAFT marks included). Changes only that page's content, and only while it still has the seed's
 * earlier layout (the Page top block, no Page header). The previous content stays in the page's
 * revisions. The "Map embed query" option is filled by the seed (only when never saved).
 *
 * Run: wp eval-file dev/migrations/2026-09-27-contact-v2.php --user=1   (after committing acf-json)
 *
 * @package panmotors
 */

require_once dirname( __DIR__ ) . '/lib.php';

$pm_id = basename( __FILE__, '.php' );
if ( ! panmotors_migration_begin( $pm_id ) ) {
	return;
}

$pm_page = panmotors_dev_page( 'contact' );
$pm_old  = $pm_page ? (string) get_post_field( 'post_content', $pm_page ) : '';
$pm_faq  = '';
foreach ( parse_blocks( $pm_old ) as $pm_block ) {
	if ( 'pm/faq' === $pm_block['blockName'] ) {
		$pm_faq = serialize_block( $pm_block );
		break;
	}
}

if ( ! $pm_page ) {
	WP_CLI::log( 'No Contact page: nothing to change (the seed creates it with the new layout).' );
} elseif ( false === strpos( $pm_old, '<!-- wp:pm/page-hero' ) || false !== strpos( $pm_old, '<!-- wp:pm/page-header' ) ) {
	WP_CLI::warning( 'Contact no longer has the earlier layout: left as it is.' );
} else {
	wp_update_post(
		array(
			'ID'           => $pm_page,
			'post_content' => wp_slash( panmotors_demo_contact_content( $pm_faq ) ),
		)
	);
	WP_CLI::log( "Contact (page {$pm_page}): v2 layout" . ( $pm_faq ? ', questions carried over.' : ', no questions found.' ) );
}

panmotors_migration_done( $pm_id );
