<?php
/**
 * ACF integration: local JSON and the options page.
 *
 * Field groups live in acf-json/ and are committed. ACF saves back to
 * acf-json/ whenever a group is edited in wp-admin.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether ACF Pro (repeaters, gallery, options pages) is available.
 *
 * @return bool
 */
function panmotors_has_acf_pro() {
	return function_exists( 'acf_add_options_page' ) && function_exists( 'get_field' );
}

/**
 * Save field groups to the theme's acf-json/ folder.
 *
 * @return string
 */
function panmotors_acf_json_save_path() {
	return PANMOTORS_DIR . '/acf-json';
}
add_filter( 'acf/settings/save_json', 'panmotors_acf_json_save_path' );

/**
 * Load field groups from the theme's acf-json/ folder only.
 *
 * @param string[] $paths Load paths.
 * @return string[]
 */
function panmotors_acf_json_load_paths( $paths ) {
	unset( $paths[0] );
	$paths[] = PANMOTORS_DIR . '/acf-json';
	return $paths;
}
add_filter( 'acf/settings/load_json', 'panmotors_acf_json_load_paths' );

/**
 * Register the "Pan Motors" options page. Location rule: options_page == panmotors-settings.
 */
function panmotors_acf_options_page() {
	if ( ! panmotors_has_acf_pro() ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title' => __( 'Pan Motors settings', 'panmotors' ),
			'menu_title' => __( 'Pan Motors', 'panmotors' ),
			'menu_slug'  => 'panmotors-settings',
			'capability' => 'edit_theme_options',
			'position'   => 59,
			'icon_url'   => 'dashicons-store',
			'redirect'   => false,
			'autoload'   => true,
		)
	);
}
add_action( 'acf/init', 'panmotors_acf_options_page' );

/**
 * Warn admins when ACF Pro is missing. The front end still renders, with empty sections hidden.
 */
function panmotors_acf_missing_notice() {
	if ( panmotors_has_acf_pro() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'The Pan Motors theme needs Advanced Custom Fields PRO. Install and activate it to edit the site content.', 'panmotors' )
	);
}
add_action( 'admin_notices', 'panmotors_acf_missing_notice' );
