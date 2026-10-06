<?php
/**
 * Migration (TASKS 4d, 6 Oct 2026): the Instagram button of the Pan Motors Live block reads
 * "Follow the journey" instead of "Follow the floor".
 *
 * Changes only pm/live blocks whose button text is still exactly "Follow the floor", in pages and
 * synced patterns (published, draft, pending, private or scheduled). A text the client changed is
 * left alone, and so is the link (Pan Motors settings → Social → Instagram). The previous content
 * stays in the page's revisions. The new default for new blocks is in dev/acf-fields.py.
 *
 * Run: wp eval-file dev/migrations/2026-10-06-follow-the-journey.php --user=1
 *
 * @package panmotors
 */

require_once dirname( __DIR__ ) . '/lib.php';

$pm_id = basename( __FILE__, '.php' );
if ( ! panmotors_migration_begin( $pm_id ) ) {
	return;
}

// The block's saved field, exactly as ACF writes it in the block comment.
$pm_old = '"live_cta_label":"Follow the floor"';
$pm_new = '"live_cta_label":"Follow the journey"';

$pm_posts = get_posts(
	array(
		'post_type'      => array( 'page', 'wp_block' ),
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'posts_per_page' => -1,
		's'              => 'Follow the floor',
		'search_columns' => array( 'post_content' ),
	)
);

$pm_changed = 0;
foreach ( $pm_posts as $pm_post ) {
	$pm_content = (string) $pm_post->post_content;
	$pm_count   = substr_count( $pm_content, $pm_old );
	if ( ! $pm_count ) {
		continue;
	}
	wp_update_post(
		array(
			'ID'           => $pm_post->ID,
			'post_content' => wp_slash( str_replace( $pm_old, $pm_new, $pm_content ) ),
		)
	);
	$pm_changed += $pm_count;
	WP_CLI::log( "{$pm_post->post_type} {$pm_post->ID} ({$pm_post->post_title}): {$pm_count} button text changed." );
}

if ( ! $pm_changed ) {
	WP_CLI::log( 'No block still says "Follow the floor": nothing to change.' );
}

panmotors_migration_done( $pm_id );
