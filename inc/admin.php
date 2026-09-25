<?php
/**
 * wp-admin for the client, who logs in as an Editor.
 *
 * Editors edit the pages, the Pan Motors settings (not the Technical tab), menus, the logo
 * (Customizer → Site Identity) and media. They cannot reach theme files, plugins or users,
 * and the unused Posts and Comments screens are hidden.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current user is the client (can edit pages but is not an administrator).
 *
 * @return bool
 */
function panmotors_is_client() {
	return is_user_logged_in() && current_user_can( 'edit_others_pages' ) && ! current_user_can( 'manage_options' );
}

/**
 * Let Editors manage menus and the logo (Customizer → Site Identity).
 *
 * The capability is added to the Editor role while this theme is active and removed when the
 * theme is switched away. It has to live on the role: WordPress checks it for the Customizer
 * before the theme's functions.php loads. edit_theme_options does not include switching or
 * editing themes, plugins or users.
 */
function panmotors_editor_role_caps() {
	$editor = get_role( 'editor' );
	if ( $editor && ! $editor->has_cap( 'edit_theme_options' ) ) {
		$editor->add_cap( 'edit_theme_options' );
	}
}
add_action( 'after_setup_theme', 'panmotors_editor_role_caps' );

/**
 * Take the capability back when another theme is activated.
 */
function panmotors_editor_role_caps_remove() {
	$editor = get_role( 'editor' );
	if ( $editor ) {
		$editor->remove_cap( 'edit_theme_options' );
	}
}
add_action( 'switch_theme', 'panmotors_editor_role_caps_remove' );

/**
 * Hide Posts and Comments (the site has no blog), and the Themes, Patterns and Fonts screens
 * that come with menu access, for the client.
 */
function panmotors_client_menus() {
	if ( ! panmotors_is_client() ) {
		return;
	}
	remove_menu_page( 'edit.php' );
	remove_menu_page( 'edit-comments.php' );
	remove_submenu_page( 'themes.php', 'themes.php' );
	remove_submenu_page( 'themes.php', 'widgets.php' );
	remove_submenu_page( 'themes.php', 'site-editor.php?p=/pattern' );
	remove_submenu_page( 'themes.php', 'font-library.php' );
}
add_action( 'admin_menu', 'panmotors_client_menus', 999 );

/**
 * Send the client away from the hidden screens if they open them directly.
 */
function panmotors_client_redirects() {
	global $pagenow;
	if ( ! panmotors_is_client() ) {
		return;
	}
	$post_type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : 'post'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$blocked   = in_array( $pagenow, array( 'edit-comments.php', 'comment.php', 'widgets.php', 'themes.php', 'site-editor.php', 'font-library.php' ), true )
		|| ( in_array( $pagenow, array( 'edit.php', 'post-new.php' ), true ) && 'post' === $post_type );
	if ( $blocked ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}
add_action( 'admin_init', 'panmotors_client_redirects' );

/**
 * Remove "New post" and Comments from the toolbar for the client.
 *
 * @param WP_Admin_Bar $bar Toolbar.
 */
function panmotors_client_toolbar( $bar ) {
	if ( panmotors_is_client() ) {
		$bar->remove_node( 'new-post' );
		$bar->remove_node( 'comments' );
	}
}
add_action( 'admin_bar_menu', 'panmotors_client_toolbar', 999 );

/**
 * Customizer for the client: logo and menus only. No Additional CSS, and the homepage setting
 * cannot be changed (the theme depends on the static front page).
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function panmotors_client_customizer( $wp_customize ) {
	if ( ! panmotors_is_client() ) {
		return;
	}
	$wp_customize->remove_section( 'custom_css' );
	$wp_customize->remove_section( 'static_front_page' );
}
add_action( 'customize_register', 'panmotors_client_customizer', 999 );
