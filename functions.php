<?php
/**
 * Pan Motors theme bootstrap.
 *
 * Only loads files from inc/. Keep logic out of this file.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

define( 'PANMOTORS_DIR', get_template_directory() );
define( 'PANMOTORS_URI', get_template_directory_uri() );

foreach ( array( 'setup', 'enqueue', 'acf', 'admin', 'schema', 'helpers' ) as $panmotors_file ) {
	require_once PANMOTORS_DIR . '/inc/' . $panmotors_file . '.php';
}
unset( $panmotors_file );
