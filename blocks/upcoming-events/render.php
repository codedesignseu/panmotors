<?php
/**
 * Block pm/upcoming-events (Upcoming events, Home). The next events from the Events post type
 * (pm_event), soonest first, as the rows of the Events page. Data from the block fields, markup in
 * the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_limit = (int) get_field( 'upcoming_limit' );

panmotors_render_block(
	'template-parts/sections/upcoming-events',
	'',
	array(
		'eyebrow' => get_field( 'upcoming_eyebrow' ),
		'title'   => get_field( 'upcoming_title' ),
		'intro'   => get_field( 'upcoming_intro' ),
		'more'    => get_field( 'upcoming_more_label' ),
		'link'    => get_field( 'upcoming_more_link' ),
		'view'    => get_field( 'upcoming_view_label' ),
		'events'  => array_slice( panmotors_events()['upcoming'], 0, $panmotors_limit > 0 ? $panmotors_limit : 2 ), // Emptied field: the default.
	),
	$is_preview,
	__( 'Upcoming events: no event is coming up, so this section is hidden. Add events under Events.', 'panmotors' )
);
