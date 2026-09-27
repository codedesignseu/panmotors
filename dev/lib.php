<?php
/**
 * Shared helpers for the dev scripts: the seed (dev/seed.php) and the migrations
 * (dev/migrations/). Development only, excluded from deploys via .distignore.
 *
 * - panmotors_dev_backup(): database export to dev/.cache/db/ before anything is written.
 * - panmotors_seed_created(): the registry of what the seed has created, so it never recreates
 *   something the client deleted.
 * - panmotors_migration_begin() / _done(): run each migration once.
 * - Block markup builders (panmotors_seed_block() and friends).
 *
 * @package panmotors
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Development only: run through WP-CLI.\n" );
}

/**
 * Export the database to dev/.cache/db/<label>-<time>.sql. Stops the script if the export fails,
 * so nothing is written without a backup. Keeps the newest 20 exports.
 *
 * @param string $label File name prefix, e.g. 'seed'.
 * @return string Path of the export.
 */
function panmotors_dev_backup( $label ) {
	$dir = dirname( __DIR__ ) . '/dev/.cache/db';
	wp_mkdir_p( $dir );
	$file = $dir . '/' . sanitize_file_name( $label ) . '-' . gmdate( 'Ymd-His' ) . '.sql';

	// Local by Flywheel: the database listens on a socket that only PHP's ini knows about.
	$socket = (string) ini_get( 'mysqli.default_socket' );
	$cmd    = 'db export ' . escapeshellarg( $file ) . ( $socket && file_exists( $socket ) ? ' --socket=' . escapeshellarg( $socket ) : '' );
	$result = WP_CLI::runcommand(
		$cmd,
		array(
			'return'     => 'all',
			'exit_error' => false,
			'launch'     => true,
		)
	);
	if ( $result->return_code || ! file_exists( $file ) || filesize( $file ) < 1024 ) {
		WP_CLI::error( "Database export failed, nothing was changed.\n" . $result->stderr );
	}

	$old = glob( $dir . '/*.sql' );
	sort( $old );
	foreach ( array_slice( $old, 0, max( 0, count( $old ) - 20 ) ) as $gone ) {
		unlink( $gone ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
	}

	WP_CLI::log( 'Database exported to ' . str_replace( dirname( __DIR__ ) . '/', '', $file ) );
	return $file;
}

/**
 * Whether the seed runs with --reset-demo (overwrite demo content).
 *
 * @return bool
 */
function panmotors_seed_reset() {
	return defined( 'PANMOTORS_SEED_RESET' ) && PANMOTORS_SEED_RESET;
}

/**
 * Registry of what the seed has created (option panmotors_seed_created). A key in it is never
 * created again, so demo content the client deleted stays deleted. --reset-demo ignores it.
 *
 * @param string $key  e.g. 'car:mclaren', 'page:about', 'media:<file>'.
 * @param bool   $mark Record the key.
 * @return bool Whether the key was recorded before this call.
 */
function panmotors_seed_created( $key, $mark = false ) {
	$done = (array) get_option( 'panmotors_seed_created', array() );
	$was  = isset( $done[ $key ] );
	if ( $mark && ! $was ) {
		$done[ $key ] = gmdate( 'c' );
		update_option( 'panmotors_seed_created', $done, false );
	}
	return $was;
}

/**
 * Start a one-off migration: stops when it already ran, else exports the database.
 *
 * @param string $id Migration id (its file name without .php).
 * @return bool False when it already ran.
 */
function panmotors_migration_begin( $id ) {
	if ( 1 !== get_current_user_id() ) {
		WP_CLI::error( 'Run migrations as the administrator: --user=1' );
	}
	$done = (array) get_option( 'panmotors_migrations', array() );
	if ( isset( $done[ $id ] ) ) {
		WP_CLI::log( "Migration {$id} already ran on {$done[ $id ]}. Nothing to do." );
		return false;
	}
	panmotors_dev_backup( 'migration-' . $id );
	return true;
}

/**
 * Record a migration as done.
 *
 * @param string $id Migration id.
 */
function panmotors_migration_done( $id ) {
	$done        = (array) get_option( 'panmotors_migrations', array() );
	$done[ $id ] = gmdate( 'c' );
	update_option( 'panmotors_migrations', $done, false );
	WP_CLI::success( "Migration {$id} done." );
}

/**
 * Attachment imported by the seed from _design/uploads, by its original file name.
 *
 * @param string $original File name in _design/uploads.
 * @return int Attachment ID, or 0.
 */
function panmotors_dev_media( $original ) {
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_pm_seed_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $original, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * ID of a published page by slug, or 0. For link fields in demo content only.
 *
 * @param string $slug Page slug.
 * @return int
 */
function panmotors_dev_page( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? (int) $page->ID : 0;
}

/**
 * ACF block data for a pm/* block, from field names. Field keys come from the block's field
 * group (acf-json), so seed data never repeats them. Repeaters are flattened the way ACF stores
 * them: rows_0_sub, and the row count under the repeater name.
 *
 * @param string $block  Block name, e.g. 'pm/hero'.
 * @param array  $values Field name => value.
 * @return array Block attributes.
 */
function panmotors_seed_block_attrs( $block, $values ) {
	$group  = 'group_pm_block_' . str_replace( '-', '_', substr( $block, 3 ) );
	$fields = array();
	foreach ( acf_get_fields( $group ) as $field ) {
		$fields[ $field['name'] ] = $field;
	}

	$data    = array();
	$flatten = static function ( $prefix, $field, $value ) use ( &$data, &$flatten ) {
		if ( 'repeater' === $field['type'] ) {
			$subs = array_column( $field['sub_fields'], null, 'name' );
			foreach ( array_values( (array) $value ) as $i => $row ) {
				foreach ( $row as $name => $sub_value ) {
					if ( isset( $subs[ $name ] ) ) {
						$flatten( "{$prefix}_{$i}_{$name}", $subs[ $name ], $sub_value );
					}
				}
			}
			$data[ $prefix ]       = count( (array) $value );
			$data[ '_' . $prefix ] = $field['key'];
			return;
		}
		$data[ $prefix ]       = is_array( $value ) ? array_map( 'strval', $value ) : $value;
		$data[ '_' . $prefix ] = $field['key'];
	};

	foreach ( $values as $name => $value ) {
		if ( ! isset( $fields[ $name ] ) ) {
			WP_CLI::error( "Seed: no field '{$name}' in block {$block}." );
		}
		$flatten( $name, $fields[ $name ], $value );
	}

	return array(
		'name' => $block,
		'data' => $data,
		'mode' => 'preview',
	);
}

/**
 * One pm/* block as markup.
 *
 * @param string $block  Block name.
 * @param array  $values Field name => value.
 * @param array  $extra  Extra attributes (e.g. lock).
 * @return string
 */
function panmotors_seed_block( $block, $values = array(), $extra = array() ) {
	return serialize_block(
		array(
			'blockName'    => $block,
			'attrs'        => array_merge( panmotors_seed_block_attrs( $block, $values ), $extra ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	);
}

/**
 * Core paragraphs as block markup, from simple HTML paragraphs.
 *
 * @param string $html One or more <p> elements.
 * @return string
 */
function panmotors_seed_paragraphs( $html ) {
	preg_match_all( '#<p>(.*?)</p>#s', $html, $m );
	return implode( "\n\n", array_map( static fn( $p ) => "<!-- wp:paragraph -->\n<p>{$p}</p>\n<!-- /wp:paragraph -->", $m[1] ) );
}

