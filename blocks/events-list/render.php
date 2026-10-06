<?php
/**
 * Block pm/events-list (Events list). The events come from the Events post type (pm_event): upcoming
 * soonest first, past newest first. Labels and the empty state are block fields.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_events = panmotors_events();
$panmotors_past   = get_field( 'events_show_past' ) || null === get_field( 'events_show_past' ) ? $panmotors_events['past'] : array();
$panmotors_limit  = (int) get_field( 'events_past_limit' );
if ( $panmotors_limit > 0 ) {
	$panmotors_past = array_slice( $panmotors_past, 0, $panmotors_limit );
}

panmotors_render_block(
	'template-parts/sections/events-list',
	'events-list',
	array(
		'upcoming'    => $panmotors_events['upcoming'],
		'past'        => $panmotors_past,
		'intro'       => get_field( 'events_intro' ),
		'tab_up'      => get_field( 'events_tab_upcoming' ),
		'tab_past'    => get_field( 'events_tab_past' ),
		'view'        => get_field( 'events_view_label' ),
		'empty_text'  => get_field( 'events_empty_text' ),
		'empty_label' => get_field( 'events_empty_label' ),
		'empty_link'  => get_field( 'events_empty_link' ),
	),
	$is_preview
);
