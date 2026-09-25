<?php
/**
 * Homepage: hero, marquee, then section previews in design order.
 *
 * A section switched off on the Home edit screen ("Show this section") or whose fields are empty
 * prints nothing, and its JS module is not loaded.
 * Home matches _design/index.html exactly: nothing is added that the design does not have.
 *
 * @package panmotors
 */

get_header();

// Design order. Hero and marquee are home-only; the rest are section components in
// 'home' context, reading their content from their own page (pages.md, D9/D10).
panmotors_render_section( 'template-parts/front/hero', 'hero-video' );
if ( panmotors_section_on( 'marquee' ) ) {
	panmotors_render_section( 'template-parts/front/marquee' );
}
if ( panmotors_section_on( 'featured' ) ) {
	panmotors_render_section(
		'template-parts/sections/featured-cars',
		'',
		array(
			'page_id' => panmotors_page( 'featured' ),
			'context' => 'home',
			'limit'   => 4,
		)
	);
}
if ( panmotors_section_on( 'values' ) ) {
	panmotors_render_section(
		'template-parts/sections/values',
		'',
		array(
			'page_id' => panmotors_page( 'about' ),
			'context' => 'home',
		)
	);
}
if ( panmotors_section_on( 'about' ) ) {
	panmotors_render_section(
		'template-parts/sections/about',
		'heritage-fade',
		array(
			'page_id' => panmotors_page( 'about' ),
			'context' => 'home',
		)
	);
}
if ( panmotors_section_on( 'latest' ) ) {
	panmotors_render_section(
		'template-parts/sections/latest-cars',
		'slider-drag',
		array(
			'page_id' => panmotors_page( 'latest' ),
			'context' => 'home',
		)
	);
}
if ( panmotors_section_on( 'live' ) ) {
	panmotors_render_section(
		'template-parts/sections/live',
		'live-videos',
		array( 'page_id' => (int) get_option( 'page_on_front' ) )
	);
}
if ( panmotors_section_on( 'showroom' ) ) {
	panmotors_render_section(
		'template-parts/sections/showroom',
		'showroom-slider',
		array(
			'page_id' => panmotors_page( 'showroom' ),
			'context' => 'home',
		)
	);
}
if ( panmotors_section_on( 'enquire' ) ) {
	panmotors_render_section(
		'template-parts/sections/enquire',
		'',
		array(
			'page_id' => panmotors_page( 'contact' ),
			'context' => 'home',
		)
	);
}

get_footer();
