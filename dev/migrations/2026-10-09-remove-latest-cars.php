<?php
/**
 * Migration (9 Oct 2026): Latest Cars removed from the site.
 *
 * - Removes the pm/latest-cars block from the homepage. Nothing else on Home changes.
 * - Removes the Latest Cars page from every menu.
 * - Moves the Latest Cars page (/latest-cars/) to the Bin, so it can still be restored from
 *   Pages → Bin until WordPress empties it.
 * The block itself stays in the theme, hidden from the inserter; the cars are not touched.
 *
 * Run:
 *   wp eval-file dev/migrations/2026-10-09-remove-latest-cars.php --user=1
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
$pm_kept   = array();
$pm_gone   = 0;
foreach ( $pm_blocks as $pm_i => $pm_block ) {
	if ( 'pm/latest-cars' === $pm_block['blockName'] ) {
		++$pm_gone;
		// Drop the whitespace between it and the next block too, so the blocks stay one blank line apart.
		if ( isset( $pm_blocks[ $pm_i + 1 ] ) && null === $pm_blocks[ $pm_i + 1 ]['blockName'] && '' === trim( $pm_blocks[ $pm_i + 1 ]['innerHTML'] ) ) {
			$pm_blocks[ $pm_i + 1 ]['skip'] = true;
		}
		continue;
	}
	if ( empty( $pm_block['skip'] ) ) {
		$pm_kept[] = $pm_block;
	}
}
if ( $pm_gone ) {
	wp_update_post(
		array(
			'ID'           => $pm_front,
			'post_content' => wp_slash( serialize_blocks( $pm_kept ) ),
		)
	);
	WP_CLI::log( 'Home: Latest Cars block removed.' );
} else {
	WP_CLI::log( 'Home has no Latest Cars block.' );
}

$pm_page = panmotors_dev_page( 'latest-cars' );
if ( $pm_page ) {
	foreach ( wp_get_nav_menus() as $pm_menu ) {
		foreach ( (array) wp_get_nav_menu_items( $pm_menu->term_id ) as $pm_item ) {
			if ( 'post_type' === $pm_item->type && (int) $pm_item->object_id === $pm_page ) {
				wp_delete_post( $pm_item->ID, true );
				WP_CLI::log( "Menu {$pm_menu->name}: Latest Cars removed." );
			}
		}
	}
	wp_trash_post( $pm_page );
	WP_CLI::log( "Latest Cars page ({$pm_page}) moved to the Bin." );
} else {
	WP_CLI::log( 'No Latest Cars page.' );
}

panmotors_migration_done( $pm_id );
