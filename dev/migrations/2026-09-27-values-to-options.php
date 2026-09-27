<?php
/**
 * Migration (D12 step 2, 27 Sep 2026): Our Values moves from the "Our Values" synced pattern to
 * Options, and Home's Featured Cars block gets the "All featured cars" button.
 * Was part of the seed on 27 Sep 2026.
 *
 * - Each page that shows the synced pattern the seed created gets the pm/values block in its place
 *   (Dark cards on Home, Light section elsewhere); then that pattern is deleted. Other patterns are
 *   not touched.
 * - Home's first Featured Cars block gets the button fields, when it has none yet.
 * The values themselves are filled by the seed (Options → Our Values, when never saved).
 *
 * Run: wp eval-file dev/migrations/2026-09-27-values-to-options.php --user=1
 *
 * @package panmotors
 */

require_once dirname( __DIR__ ) . '/lib.php';

$pm_id = basename( __FILE__, '.php' );
if ( ! panmotors_migration_begin( $pm_id ) ) {
	return;
}

$pm_front = (int) get_option( 'page_on_front' );

$pm_pattern = get_posts(
	array(
		'post_type'      => 'wp_block',
		'post_status'    => 'any',
		'meta_key'       => '_pm_seed_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'     => 'values', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		'posts_per_page' => 1,
		'fields'         => 'ids',
	)
);
if ( $pm_pattern ) {
	$pm_dark  = panmotors_seed_block(
		'pm/values',
		array(
			'values_style' => 'dark',
			'values_title' => 'Our Values',
			'values_link'  => panmotors_dev_page( 'about' ),
		)
	);
	$pm_light = panmotors_seed_block(
		'pm/values',
		array(
			'values_style' => 'light',
			'values_title' => 'Our Values',
			'values_intro' => 'Four things we hold to with every car and every client.',
		)
	);
	$pm_ref   = '/<!-- wp:block \{"ref":' . (int) $pm_pattern[0] . '[,}][^>]*\/-->/';
	foreach ( get_posts( array( 'post_type' => array( 'page', 'wp_block' ), 'post_status' => 'any', 'posts_per_page' => -1 ) ) as $pm_page ) {
		if ( ! preg_match( $pm_ref, $pm_page->post_content ) ) {
			continue;
		}
		$pm_block = $pm_front === $pm_page->ID ? $pm_dark : $pm_light;
		wp_update_post(
			array(
				'ID'           => $pm_page->ID,
				'post_content' => wp_slash( preg_replace_callback( $pm_ref, static fn() => $pm_block, $pm_page->post_content ) ),
			)
		);
		WP_CLI::log( "Page {$pm_page->ID}: Our Values pattern replaced by the pm/values block." );
	}
	wp_delete_post( (int) $pm_pattern[0], true );
	WP_CLI::log( "Synced pattern Our Values ({$pm_pattern[0]}) deleted." );
}

$pm_blocks = $pm_front ? parse_blocks( get_post_field( 'post_content', $pm_front ) ) : array();
foreach ( $pm_blocks as &$pm_block ) {
	if ( 'pm/featured-cars' !== $pm_block['blockName'] ) {
		continue;
	}
	if ( ! array_key_exists( 'featured_more_label', $pm_block['attrs']['data'] ?? array() ) ) {
		$pm_more                   = panmotors_seed_block_attrs(
			'pm/featured-cars',
			array(
				'featured_more_label' => 'All featured cars',
				'featured_more_link'  => panmotors_dev_page( 'featured-cars' ),
			)
		);
		$pm_block['attrs']['data'] = array_merge( (array) ( $pm_block['attrs']['data'] ?? array() ), $pm_more['data'] );
		wp_update_post(
			array(
				'ID'           => $pm_front,
				'post_content' => wp_slash( serialize_blocks( $pm_blocks ) ),
			)
		);
		WP_CLI::log( 'Home: "All featured cars" button added to Featured Cars.' );
	}
	break;
}
unset( $pm_block );

panmotors_migration_done( $pm_id );
