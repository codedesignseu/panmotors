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
 * @param string      $class_name CSS class for the <img>.
 * @param string|null $alt        Alt text. Null keeps the media library alt, '' marks it decorative.
 * @param string      $sizes      Rendered width for the sizes attribute, e.g. '84px'.
 * @param bool        $high       fetchpriority="high" (the header logo is the LCP element: the
 *                                full-viewport hero image is ignored by Chrome's LCP).
 */
function panmotors_logo_image( $class_name, $alt = null, $sizes = '100px', $high = false ) {
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	if ( ! $logo_id ) {
		return;
	}

	$attr = array(
		'class'   => $class_name,
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
 * @param string $class_name CSS class for the link.
 */
function panmotors_logo( $class_name ) {
	$name = panmotors_option( 'trading_name', get_bloginfo( 'name' ) );

	printf( '<a class="%s" href="%s" rel="home">', esc_attr( $class_name ), esc_url( home_url( '/' ) ) );

	if ( get_theme_mod( 'custom_logo' ) ) {
		panmotors_logo_image( $class_name . '-img', $name, '84px', true ); // 46px tall.
	} else {
		echo '<span class="' . esc_attr( $class_name ) . '-text">' . esc_html( $name ) . '</span>';
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

/**
 * Label of the first breadcrumb: the static front page's title, or "Home" when there is none.
 *
 * @return string
 */
function panmotors_home_label() {
	$front = (int) get_option( 'page_on_front' );
	$title = $front ? get_the_title( $front ) : '';
	return '' !== $title ? $title : __( 'Home', 'panmotors' );
}

/**
 * A YouTube or Vimeo link as a privacy-friendly player address: youtube-nocookie.com for YouTube,
 * Vimeo with Do Not Track. Other sites are not accepted. Used by the About and Story videos
 * (pm/about, pm/story), which load the player only when the visitor presses play.
 *
 * Accepts youtube.com/watch?v=…, youtu.be/…, youtube.com/shorts/…, youtube.com/embed/…,
 * vimeo.com/123, vimeo.com/123/hash (unlisted) and player.vimeo.com/video/123.
 *
 * @param string $url Video page address.
 * @return array|null { provider: 'youtube'|'vimeo', src: player URL with autoplay }, or null.
 */
function panmotors_video_embed( $url ) {
	$url   = trim( (string) $url );
	$parts = wp_parse_url( $url );
	if ( ! $parts || empty( $parts['host'] ) || ! in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true ) ) {
		return null;
	}
	$host = preg_replace( '/^(www\.|m\.)/', '', strtolower( $parts['host'] ) );
	$path = trim( (string) ( $parts['path'] ?? '' ), '/' );
	parse_str( (string) ( $parts['query'] ?? '' ), $query );

	if ( in_array( $host, array( 'youtube.com', 'youtu.be', 'youtube-nocookie.com' ), true ) ) {
		$id = '';
		if ( 'youtu.be' === $host ) {
			$id = strtok( $path, '/' );
		} elseif ( 'watch' === $path ) {
			$id = (string) ( $query['v'] ?? '' );
		} elseif ( preg_match( '#^(?:shorts|embed|live)/([^/]+)#', $path, $m ) ) {
			$id = $m[1];
		}
		if ( ! preg_match( '/^[A-Za-z0-9_-]{6,20}$/', (string) $id ) ) {
			return null;
		}
		return array(
			'provider' => 'youtube',
			'src'      => 'https://www.youtube-nocookie.com/embed/' . $id . '?autoplay=1&rel=0&playsinline=1',
		);
	}

	if ( in_array( $host, array( 'vimeo.com', 'player.vimeo.com' ), true ) ) {
		if ( ! preg_match( '#^(?:video/)?(\d{5,12})(?:/([0-9a-f]{6,20}))?$#', $path, $m ) ) {
			return null;
		}
		$hash = $m[2] ?? ( isset( $query['h'] ) && preg_match( '/^[0-9a-f]{6,20}$/', (string) $query['h'] ) ? $query['h'] : '' );
		return array(
			'provider' => 'vimeo',
			'src'      => 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&dnt=1' . ( $hash ? '&h=' . $hash : '' ),
		);
	}

	return null;
}

/**
 * The video of a block with a Media type field (pm/about on Home, pm/story on About), read from
 * the block's own fields: "{prefix}_media" (image or video), "{prefix}_video_source" (upload or
 * link), "{prefix}_video_file" and "{prefix}_video_url". Call it inside the block's render.php.
 * Nothing in the editor preview: the poster stands in for the video there.
 *
 * @param string $prefix     Field name prefix, e.g. 'about'.
 * @param bool   $is_preview Editor preview.
 * @return array { kind: 'upload', src, type } or { kind: 'embed', src }; empty for an image, an
 *               editor preview, or a video that is not filled in.
 */
function panmotors_block_video( $prefix, $is_preview ) {
	if ( $is_preview || 'video' !== get_field( $prefix . '_media' ) ) {
		return array();
	}
	if ( 'link' === get_field( $prefix . '_video_source' ) ) {
		$embed = panmotors_video_embed( (string) get_field( $prefix . '_video_url' ) );
		return $embed ? array(
			'kind' => 'embed',
			'src'  => $embed['src'],
		) : array();
	}
	$file = (int) get_field( $prefix . '_video_file' );
	$url  = $file ? wp_get_attachment_url( $file ) : '';
	return $url ? array(
		'kind' => 'upload',
		'src'  => $url,
		'type' => (string) get_post_mime_type( $file ),
	) : array();
}
