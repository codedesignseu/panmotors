<?php
/**
 * Block pm/events-intro (Events intro, Home; _design/home-events/events-intro.html). The next
 * events from the Events post type (pm_event), soonest first, without cancelled events. Data from
 * the block fields, markup in the section view.
 *
 * The button goes to the block's own page, else Settings → Events → Events page, else the
 * pm_event archive (none today, so no button).
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_limit = (int) get_field( 'ei_limit' );
$panmotors_limit = $panmotors_limit > 0 ? min( $panmotors_limit, 6 ) : 3; // Emptied field: the default.
$panmotors_link  = (string) get_field( 'ei_link' );
if ( ! $panmotors_link ) {
	$panmotors_page = (int) get_field( 'events_page', 'option' );
	$panmotors_link = $panmotors_page && 'publish' === get_post_status( $panmotors_page ) ? (string) get_permalink( $panmotors_page ) : (string) get_post_type_archive_link( 'pm_event' );
}

panmotors_render_block(
	'template-parts/sections/events-intro',
	'',
	array(
		'eyebrow' => get_field( 'ei_eyebrow' ),
		'title'   => get_field( 'ei_title' ),
		'intro'   => get_field( 'ei_intro' ),
		'label'   => get_field( 'ei_label' ),
		'link'    => $panmotors_link,
		'empty'   => get_field( 'ei_empty' ),
		'events'  => array_slice(
			array_values( array_filter( panmotors_events()['upcoming'], static fn( $e ) => 'cancelled' !== $e['status'] ) ),
			0,
			$panmotors_limit
		),
	),
	$is_preview
);
