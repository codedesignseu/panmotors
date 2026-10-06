<?php
/**
 * Capability check for the client's Editor role. Run: wp eval-file dev/tests/editor-caps.php <login>
 *
 * @package panmotors
 */

$user = get_user_by( 'login', $args[0] ?? 'pm-editor-test' );
wp_set_current_user( $user->ID );
$front = (int) get_option( 'page_on_front' );
$out   = array();
$check = array(
	'edit Home page'           => current_user_can( 'edit_post', $front ),
	'edit inner pages'         => current_user_can( 'edit_post', get_page_by_path( 'about' )->ID ?? 0 ) && current_user_can( 'edit_post', panmotors_contact_page() ),
	'cars (add, edit, delete)' => current_user_can( 'edit_pages' ) && current_user_can( 'publish_pages' ) && current_user_can( 'delete_pages' ),
	'synced patterns'          => current_user_can( 'edit_posts' ),
	'events (add, edit, publish, trash)' => current_user_can( get_post_type_object( 'pm_event' )->cap->create_posts ) && current_user_can( get_post_type_object( 'pm_event' )->cap->edit_others_posts ) && current_user_can( get_post_type_object( 'pm_event' )->cap->publish_posts ) && current_user_can( get_post_type_object( 'pm_event' )->cap->delete_others_posts ) && current_user_can( get_post_type_object( 'pm_event' )->cap->delete_published_posts ),
	'Pan Motors settings'      => current_user_can( 'edit_others_pages' ),
	'menus (edit_theme_options)' => current_user_can( 'edit_theme_options' ),
	'logo (customize)'         => current_user_can( 'customize' ),
	'media (upload_files)'     => current_user_can( 'upload_files' ),
	'NOT theme files (edit_themes)' => ! current_user_can( 'edit_themes' ),
	'NOT switch themes'        => ! current_user_can( 'switch_themes' ),
	'NOT plugins'              => ! current_user_can( 'activate_plugins' ) && ! current_user_can( 'install_plugins' ) && ! current_user_can( 'edit_plugins' ),
	'NOT users'                => ! current_user_can( 'list_users' ) && ! current_user_can( 'create_users' ) && ! current_user_can( 'edit_users' ),
	'NOT options (manage_options)' => ! current_user_can( 'manage_options' ),
	'NOT ACF field groups'     => ! current_user_can( acf_get_setting( 'capability' ) ),
);
foreach ( $check as $label => $ok ) {
	$out[] = ( $ok ? 'OK   ' : 'FAIL ' ) . $label;
}
echo implode( "\n", $out ), "\n";
