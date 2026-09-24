<?php
/**
 * Homepage: hero, marquee, then section previews in design order.
 *
 * A section whose fields are empty prints nothing, and its JS module is not loaded.
 * Home matches _design/index.html exactly: nothing is added that the design does not have.
 *
 * @package panmotors
 */

get_header();

// Design order. Hero and marquee are home-only; the rest are section components in
// 'home' context, reading their content from their own page (pages.md, D9/D10).
panmotors_render_section( 'template-parts/front/hero', 'hero-video' );
panmotors_render_section( 'template-parts/front/marquee' );
panmotors_render_section(
	'template-parts/sections/featured-cars',
	'',
	array(
		'page_id' => panmotors_page( 'featured' ),
		'context' => 'home',
		'limit'   => 4,
	)
);
panmotors_render_section(
	'template-parts/sections/values',
	'',
	array(
		'page_id' => panmotors_page( 'about' ),
		'context' => 'home',
	)
);

get_footer();
