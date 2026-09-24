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
