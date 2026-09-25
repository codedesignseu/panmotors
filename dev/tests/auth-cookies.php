<?php
/**
 * Prints login cookies for a local test user, as JSON, for headless tests.
 * Run: wp eval-file dev/tests/auth-cookies.php <login>   (local development only)
 *
 * @package panmotors
 */

$user    = get_user_by( 'login', $args[0] ?? 'pm-editor-test' );
$expire  = time() + HOUR_IN_SECONDS;
$token   = WP_Session_Tokens::get_instance( $user->ID )->create( $expire );
$cookies = array(
	array( 'name' => AUTH_COOKIE, 'value' => wp_generate_auth_cookie( $user->ID, $expire, 'auth', $token ) ),
	array( 'name' => LOGGED_IN_COOKIE, 'value' => wp_generate_auth_cookie( $user->ID, $expire, 'logged_in', $token ) ),
);
echo wp_json_encode( $cookies );
