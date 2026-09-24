<?php
/**
 * Styles, fonts and JS modules.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

/**
 * Version string for a theme asset, from its modification time.
 *
 * @param string $path Path relative to the theme root.
 * @return string|false Version, or false when the file is missing.
 */
function panmotors_asset_version( $path ) {
	$file = PANMOTORS_DIR . '/' . ltrim( $path, '/' );
	return file_exists( $file ) ? (string) filemtime( $file ) : false;
}

/**
 * Fonts preloaded in <head>. Latin subsets only; latin-ext loads on demand via unicode-range.
 *
 * @return string[] Paths relative to the theme root.
 */
function panmotors_preload_fonts() {
	return array(
		'assets/fonts/archivo-latin-wght-normal.woff2',
		'assets/fonts/bodoni-moda-latin-opsz-normal.woff2',
	);
}

/**
 * Print font preload links early in <head> so text never waits on CSS discovery.
 */
function panmotors_font_preload() {
	foreach ( panmotors_preload_fonts() as $font ) {
		if ( ! panmotors_asset_version( $font ) ) {
			continue;
		}
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( PANMOTORS_URI . '/' . $font )
		);
	}
}
add_action( 'wp_head', 'panmotors_font_preload', 1 );

/**
 * Enqueue one JS module from assets/js/. Skipped quietly if the file is missing.
 *
 * Section template parts call this when they render, so a section's JS only
 * loads when the section is on the page. Classic themes print script modules
 * in wp_footer, so enqueueing from inside the template works.
 *
 * @param string $name Module file name without extension, e.g. 'slider-drag'.
 */
function panmotors_use_module( $name ) {
	$js      = 'assets/js/' . $name . '.js';
	$version = panmotors_asset_version( $js );

	if ( $version ) {
		wp_enqueue_script_module( 'pm-' . $name, PANMOTORS_URI . '/' . $js, array(), $version );
	}
}

/**
 * Enqueue the main stylesheet and the modules every page needs.
 */
function panmotors_enqueue_assets() {
	$css = 'assets/css/main.css';
	wp_enqueue_style( 'pm-main', PANMOTORS_URI . '/' . $css, array(), panmotors_asset_version( $css ) );

	panmotors_use_module( 'menu' );
	panmotors_use_module( 'reveal' );
}
add_action( 'wp_enqueue_scripts', 'panmotors_enqueue_assets' );

/**
 * Preload the hero poster on the front page. It is the LCP image (theme-map 9.5, 9.6).
 *
 * Uses the same srcset and sizes as the <img> in template-parts/front/hero.php so the
 * browser picks the same file for both.
 */
function panmotors_hero_preload() {
	if ( ! is_front_page() ) {
		return;
	}

	$poster_id = panmotors_hero_poster_id();
	$src       = $poster_id ? wp_get_attachment_image_src( $poster_id, 'pm-hero' ) : false;

	if ( ! $src ) {
		return;
	}

	printf(
		'<link rel="preload" as="image" href="%s" imagesrcset="%s" imagesizes="100vw" fetchpriority="high">' . "\n",
		esc_url( $src[0] ),
		esc_attr( (string) wp_get_attachment_image_srcset( $poster_id, 'pm-hero' ) )
	);
}
add_action( 'wp_head', 'panmotors_hero_preload', 1 );
