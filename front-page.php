<?php
/**
 * Homepage: hero, marquee, then section previews in design order.
 *
 * A section whose fields are empty prints nothing, and its JS module is not loaded.
 *
 * @package panmotors
 */

get_header();

// Home-only parts. Section previews (featured cars, values …) are added as
// components from template-parts/sections/ with context 'home' (pages.md §3).
$panmotors_sections = array(
	'hero'    => 'hero-video',
	'marquee' => '',
);

foreach ( $panmotors_sections as $panmotors_slug => $panmotors_module ) {
	panmotors_render_section( $panmotors_slug, $panmotors_module );
}

get_footer();
