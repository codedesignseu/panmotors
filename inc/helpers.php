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

/**
 * Phone number for a tel: link. Keeps a leading + and digits only.
 *
 * @param string $number Display number, e.g. "+357 99 575 001".
 * @return string
 */
function panmotors_tel( $number ) {
	return preg_replace( '/(?!^\+)[^\d]/', '', trim( (string) $number ) );
}

/**
 * Print the custom logo image, without a link.
 *
 * @param string      $class CSS class for the <img>.
 * @param string|null $alt   Alt text. Null keeps the media library alt, '' marks it decorative.
 * @param string      $sizes Rendered width for the sizes attribute, e.g. '84px'.
 * @param bool        $high  fetchpriority="high" (the header logo is the LCP element: the
 *                           full-viewport hero image is ignored by Chrome's LCP).
 */
function panmotors_logo_image( $class, $alt = null, $sizes = '100px', $high = false ) {
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	if ( ! $logo_id ) {
		return;
	}

	$attr = array(
		'class'   => $class,
		'sizes'   => $sizes,
		'loading' => false,
	);
	if ( $high ) {
		$attr['fetchpriority'] = 'high';
	}
	if ( null !== $alt ) {
		$attr['alt'] = $alt;
	}

	echo wp_get_attachment_image( $logo_id, 'medium', false, $attr );
}

/**
 * Print the site logo linked to the homepage. Falls back to the site name as text.
 *
 * Not a heading: the page H1 belongs to the page content.
 *
 * @param string $class CSS class for the link.
 */
function panmotors_logo( $class ) {
	$name = panmotors_option( 'trading_name', get_bloginfo( 'name' ) );

	printf( '<a class="%s" href="%s" rel="home">', esc_attr( $class ), esc_url( home_url( '/' ) ) );

	if ( get_theme_mod( 'custom_logo' ) ) {
		panmotors_logo_image( $class . '-img', $name, '84px', true ); // 46px tall.
	} else {
		echo '<span class="' . esc_attr( $class ) . '-text">' . esc_html( $name ) . '</span>';
	}

	echo '</a>';
}

/**
 * Add a class to menu links, set per wp_nav_menu() call with the custom `pm_link_class` argument.
 *
 * @param array    $atts Link attributes.
 * @param WP_Post  $item Menu item.
 * @param stdClass $args wp_nav_menu() arguments.
 * @return array
 */
function panmotors_menu_link_class( $atts, $item, $args ) {
	if ( ! empty( $args->pm_link_class ) ) {
		$atts['class'] = trim( ( $atts['class'] ?? '' ) . ' ' . $args->pm_link_class );
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'panmotors_menu_link_class', 10, 3 );

/**
 * Homepage hero poster attachment ID, or 0. Read from the front page's pm/hero block, for the
 * preload in <head> (before the block renders).
 *
 * @return int
 */
function panmotors_hero_poster_id() {
	$hero = panmotors_find_block( (int) get_option( 'page_on_front' ), 'pm/hero' );
	return (int) panmotors_block_field( $hero, 'hero_poster' );
}

/**
 * The Contact page (Pan Motors settings → Technical → Contact button goes to), if published.
 * Used only as a link target: the contact pill, the mobile menu and the 404 page.
 *
 * @return int Page ID, or 0.
 */
function panmotors_contact_page() {
	$id = (int) panmotors_option( 'page_contact', 0 );
	return ( $id && 'publish' === get_post_status( $id ) ) ? $id : 0;
}
