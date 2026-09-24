<?php
/**
 * Template helpers.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read an ACF field safely. Returns $fallback when ACF is missing or the value is empty.
 *
 * @param string          $name     Field name.
 * @param int|string|bool $post_id  Post ID, 'option', or false for the current post.
 * @param mixed           $fallback Returned for empty values.
 * @return mixed
 */
function panmotors_field( $name, $post_id = false, $fallback = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $fallback;
	}

	$value = get_field( $name, $post_id );

	return ( null === $value || false === $value || '' === $value || array() === $value ) ? $fallback : $value;
}

/**
 * Read a field from the Pan Motors options page.
 *
 * @param string $name     Field name.
 * @param mixed  $fallback Returned for empty values.
 * @return mixed
 */
function panmotors_option( $name, $fallback = null ) {
	return panmotors_field( $name, 'option', $fallback );
}

/**
 * Read a repeater or gallery as an array, always.
 *
 * @param string          $name    Field name.
 * @param int|string|bool $post_id Post ID, 'option', or false for the current post.
 * @return array
 */
function panmotors_rows( $name, $post_id = false ) {
	$rows = panmotors_field( $name, $post_id, array() );
	return is_array( $rows ) ? $rows : array();
}
