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
 * JS modules. Global ones load on every page, the rest only on the front page.
 * Missing files are skipped, so modules can be added one section at a time.
 *
 * @return array<string, bool> Module name => front page only.
 */
function panmotors_js_modules() {
	return array(
		'menu'            => false,
		'reveal'          => false,
		'hero-video'      => true,
		'heritage-fade'   => true,
		'slider-drag'     => true,
		'live-videos'     => true,
		'showroom-slider' => true,
	);
}

/**
 * Enqueue main stylesheet and JS modules.
 */
function panmotors_enqueue_assets() {
	$css = 'assets/css/main.css';
	wp_enqueue_style( 'pm-main', PANMOTORS_URI . '/' . $css, array(), panmotors_asset_version( $css ) );

	// Script modules are deferred by nature. Each one checks for its own data-* hooks.
	foreach ( panmotors_js_modules() as $name => $front_only ) {
		$js      = 'assets/js/' . $name . '.js';
		$version = panmotors_asset_version( $js );

		if ( ! $version || ( $front_only && ! is_front_page() ) ) {
			continue;
		}
		wp_enqueue_script_module( 'pm-' . $name, PANMOTORS_URI . '/' . $js, array(), $version );
	}
}
add_action( 'wp_enqueue_scripts', 'panmotors_enqueue_assets' );

