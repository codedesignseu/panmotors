<?php
/**
 * Homepage: one template part per section, in design order.
 *
 * A section whose fields are empty prints nothing, and its JS module is not loaded.
 *
 * @package panmotors
 */

get_header();

$panmotors_sections = array(
	'hero'          => 'hero-video',
	'marquee'       => '',
	'featured-cars' => '',
	'values'        => '',
	'about'         => 'heritage-fade',
	'latest'        => 'slider-drag',
	'live'          => 'live-videos',
	'showroom'      => 'showroom-slider',
	'faq'           => '',
	'enquire'       => '',
);

foreach ( $panmotors_sections as $panmotors_slug => $panmotors_module ) {
	panmotors_render_section( $panmotors_slug, $panmotors_module );
}

get_footer();
