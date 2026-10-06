<?php
/**
 * Theme supports, menus and image sizes.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme supports, menus and image sizes.
 */
function panmotors_setup() {
	load_theme_textdomain( 'panmotors', PANMOTORS_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 184,
			'width'       => 400,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'panmotors' ),
			'footer'  => __( 'Footer menu', 'panmotors' ),
		)
	);

	/*
	 * Image sizes. Core already makes 768, 1024, 1536 and 2048 wide copies for srcset.
	 * Cropped sizes match the fixed aspect ratios in the design at 2x.
	 */
	add_image_size( 'pm-hero', 2400, 0 );              // Hero poster, full-bleed.
	add_image_size( 'pm-tile', 1400, 1400 );           // Featured car tiles. Uncropped: tile shape changes on hover.
	add_image_size( 'pm-wide', 1960, 1102, true );     // Latest Cars slides, 16:9.
	add_image_size( 'pm-showroom', 2160, 1350, true ); // Showroom, 16:10.
	add_image_size( 'pm-portrait', 1080, 1350, true ); // About and Live tiles, 4:5.
	add_image_size( 'pm-og', 1200, 630, true );        // Open Graph and Twitter share image.

	// Half-size crops of the fixed ratios, so phones get a matching srcset candidate.
	add_image_size( 'pm-wide-s', 980, 551, true );       // 16:9.
	add_image_size( 'pm-showroom-s', 1080, 675, true );  // 16:10.
	add_image_size( 'pm-portrait-s', 540, 675, true );   // 4:5.
}
add_action( 'after_setup_theme', 'panmotors_setup' );

/**
 * Core caps srcset candidates at 2048px. Raise it so the 2400px hero size is offered to large retina screens.
 *
 * @return int
 */
function panmotors_max_srcset_width() {
	return 2560;
}
add_filter( 'max_srcset_image_width', 'panmotors_max_srcset_width' );

/**
 * Add a `pm-js` class to <html> before paint so CSS can hide reveal targets only when JS runs.
 */
function panmotors_js_class() {
	echo "<script>document.documentElement.classList.add('pm-js');</script>\n";
}
add_action( 'wp_head', 'panmotors_js_class', 0 );

/**
 * Default meta description when no SEO plugin handles it (theme-map 9.7): an event's summary, the
 * page's hero intro, falling back to the one-sentence business description from the options.
 */
function panmotors_meta_description() {
	if ( panmotors_seo_plugin_active() ) {
		return;
	}

	$text = is_singular( 'pm_event' ) ? panmotors_event_description() : '';
	$text = $text ? $text : panmotors_page_description( ( is_page() || is_front_page() ) ? (int) get_queried_object_id() : 0 );
	if ( $text ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $text ) );
	}
}
add_action( 'wp_head', 'panmotors_meta_description', 1 );

/**
 * Rank Math or Yoast without a description for the page (none written, no template): the theme's
 * default, as above. A description written in the SEO plugin always wins.
 *
 * @param string $description The SEO plugin's description.
 * @return string
 */
function panmotors_seo_description_fallback( $description ) {
	if ( '' !== trim( (string) $description ) ) {
		return $description;
	}
	$text = is_singular( 'pm_event' ) ? panmotors_event_description() : '';
	return $text ? $text : panmotors_page_description( ( is_page() || is_front_page() ) ? (int) get_queried_object_id() : 0 );
}
add_filter( 'rank_math/frontend/description', 'panmotors_seo_description_fallback' );
add_filter( 'wpseo_metadesc', 'panmotors_seo_description_fallback' );

/**
 * Keep a fixed sizes value on lazy images that ask for it ('pm-fixed-sizes' => true in the
 * wp_get_attachment_image() attributes). WordPress prefixes sizes with "auto" on lazy images,
 * which sizes the file to the image's layout width: right for photos, wrong for the blurred
 * backdrops, which are laid out full width but blurred to 64px (sizes="400px").
 *
 * @param array $attr Image attributes.
 * @return array
 */
function panmotors_fixed_sizes( $attr ) {
	if ( ! isset( $attr['pm-fixed-sizes'] ) ) {
		return $attr;
	}
	unset( $attr['pm-fixed-sizes'] );
	if ( isset( $attr['sizes'] ) ) {
		$attr['sizes'] = preg_replace( '/^auto\s*,\s*/i', '', $attr['sizes'] );
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'panmotors_fixed_sizes' );
