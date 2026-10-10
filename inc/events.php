<?php
/**
 * Events (TASKS 4d, docs/events.md, _design/events/): a public post type with one page per event.
 *
 * - pm_event: single pages at /events/{slug}/, no archive (the Events page lists them through the
 *   pm/events-list block). In the core sitemap. Editors manage events like pages.
 * - The edit screen: title and main image (featured image), the "Event details" and
 *   "Photographs" field groups (dev/acf-fields.py), and the event story in the block editor,
 *   limited to text blocks and started with two paragraphs.
 * - Status: Automatic (upcoming until the end of the event, in the site's timezone, then past),
 *   or Cancelled, Postponed, Sold out. Only an upcoming, scheduled event shows its buttons.
 * - panmotors_event() turns a post into the row every view uses; panmotors_events() lists them.
 * - An .ics file per event (?ics=1), written by the theme.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the pm_event post type.
 */
function panmotors_register_events() {
	register_post_type(
		'pm_event',
		array(
			'labels'              => array(
				'name'                  => __( 'Events', 'panmotors' ),
				'singular_name'         => __( 'Event', 'panmotors' ),
				'menu_name'             => __( 'Events', 'panmotors' ),
				'add_new'               => __( 'Add event', 'panmotors' ),
				'add_new_item'          => __( 'Add event', 'panmotors' ),
				'edit_item'             => __( 'Edit event', 'panmotors' ),
				'new_item'              => __( 'New event', 'panmotors' ),
				'view_item'             => __( 'View event', 'panmotors' ),
				'view_items'            => __( 'View events', 'panmotors' ),
				'search_items'          => __( 'Search events', 'panmotors' ),
				'not_found'             => __( 'No events yet.', 'panmotors' ),
				'not_found_in_trash'    => __( 'No events in the bin.', 'panmotors' ),
				'all_items'             => __( 'All events', 'panmotors' ),
				'item_published'        => __( 'Event published.', 'panmotors' ),
				'item_updated'          => __( 'Event updated.', 'panmotors' ),
				'featured_image'        => __( 'Main image', 'panmotors' ),
				'set_featured_image'    => __( 'Set main image', 'panmotors' ),
				'remove_featured_image' => __( 'Remove main image', 'panmotors' ),
				'use_featured_image'    => __( 'Use as main image', 'panmotors' ),
			),
			'description'         => __( 'Evenings, drives and unveilings. Each event has its own page and is listed on the Events page.', 'panmotors' ),
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true, // The block editor.
			'has_archive'         => false,
			'rewrite'             => array(
				'slug'       => 'events',
				'with_front' => false,
				'feeds'      => false,
				'pages'      => false,
			),
			'query_var'           => true,
			'menu_position'       => 22,
			'menu_icon'           => 'dashicons-calendar-alt',
			'capability_type'     => 'page', // Editors manage events like pages, as Cars.
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			// "Add event" never starts empty: two paragraphs of story.
			'template'            => array(
				array( 'core/paragraph', array( 'placeholder' => __( 'The story of the event: what happens, and for whom.', 'panmotors' ) ) ),
				array( 'core/paragraph', array( 'placeholder' => __( 'Practical details: arrival, dress, how to register.', 'panmotors' ) ) ),
			),
		)
	);
}
add_action( 'init', 'panmotors_register_events' );

/**
 * Text blocks only in the event story.
 */
const PANMOTORS_EVENT_BLOCKS = array(
	'core/paragraph',
	'core/heading',
	'core/list',
	'core/list-item',
	'core/quote',
	'core/image',
	'core/buttons',
	'core/button',
);

/**
 * Flush the rewrite rules once after the post type is added or its address changes (option
 * panmotors_events_rewrite holds the version).
 */
function panmotors_events_flush() {
	if ( '1' !== get_option( 'panmotors_events_rewrite' ) ) {
		flush_rewrite_rules( false );
		update_option( 'panmotors_events_rewrite', '1', true );
	}
}
add_action( 'init', 'panmotors_events_flush', 99 );

/*
 * ------------------------------------------------------------------
 * Data
 * ------------------------------------------------------------------
 */

/**
 * The Events page (Pan Motors settings → Events → Events page), if published. A link target only:
 * the "All events" link, the breadcrumb and the menu's current item on an event page.
 *
 * @return int Page ID, or 0.
 */
function panmotors_events_page() {
	$id = (int) panmotors_option( 'events_page', 0 );
	return ( $id && 'publish' === get_post_status( $id ) ) ? $id : 0;
}

/**
 * A label from Pan Motors settings → Events, or the theme's default when it was emptied.
 *
 * @param string $name     Field name without the ev_label_ prefix.
 * @param string $fallback Default.
 * @return string
 */
function panmotors_event_label( $name, $fallback ) {
	$value = trim( (string) panmotors_option( 'ev_label_' . $name, '' ) );
	return '' !== $value ? $value : $fallback;
}

/**
 * Parse an ACF date (Ymd) and optional time (H:i or H:i:s) in the site's timezone.
 *
 * @param string $date Ymd.
 * @param string $time Time, or '' for the start of the day.
 * @return DateTimeImmutable|null
 */
function panmotors_event_datetime( $date, $time = '' ) {
	$date = preg_replace( '/\D/', '', (string) $date );
	if ( 8 !== strlen( $date ) ) {
		return null;
	}
	$time = preg_match( '/^(\d{1,2}):(\d{2})/', (string) $time, $m ) ? sprintf( '%02d:%02d:00', $m[1], $m[2] ) : '00:00:00';
	$dt   = DateTimeImmutable::createFromFormat( 'Ymd H:i:s', $date . ' ' . $time, wp_timezone() );
	return $dt ? $dt : null;
}

/**
 * One event as a row for the views, the schema, the calendar file and llms.txt.
 *
 * @param WP_Post|int $post Event.
 * @return array|null Null when the event has no valid start date.
 */
function panmotors_event( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'pm_event' !== $post->post_type ) {
		return null;
	}
	$id      = (int) $post->ID;
	$all_day = (bool) get_field( 'all_day', $id );
	$t_start = $all_day ? '' : trim( (string) get_field( 'start_time', $id ) );
	$t_end   = $all_day ? '' : trim( (string) get_field( 'end_time', $id ) );
	$start   = panmotors_event_datetime( (string) get_field( 'start_date', $id ), $t_start );
	if ( ! $start ) {
		return null;
	}
	$end_day = panmotors_event_datetime( (string) get_field( 'end_date', $id ) );
	$end_day = ( $end_day && $end_day >= $start->setTime( 0, 0 ) ) ? $end_day : null;

	// The moment the event is over: the end time on the last day, else the end of the last day.
	$last  = $end_day ? $end_day : $start->setTime( 0, 0 );
	$until = $t_end ? panmotors_event_datetime( $last->format( 'Ymd' ), $t_end ) : $last->setTime( 23, 59, 59 );
	if ( $until < $start ) {
		$until = $start->setTime( 23, 59, 59 ); // An end time before the start time on a one-day event.
	}
	$past = time() > $until->getTimestamp();

	$set    = (string) get_field( 'event_status', $id );
	$status = in_array( $set, array( 'cancelled', 'postponed', 'soldout' ), true ) ? $set : 'scheduled';
	if ( 'cancelled' === $status || 'postponed' === $status ) {
		$pill = 'cancelled' === $status ? panmotors_event_label( 'cancelled', __( 'Cancelled', 'panmotors' ) ) : panmotors_event_label( 'postponed', __( 'Postponed', 'panmotors' ) );
	} elseif ( $past ) {
		$pill = panmotors_event_label( 'past', __( 'Past event', 'panmotors' ) );
	} elseif ( 'soldout' === $status ) {
		$pill = panmotors_event_label( 'soldout', __( 'Sold out', 'panmotors' ) );
	} else {
		$pill = panmotors_event_label( 'upcoming', __( 'Upcoming', 'panmotors' ) );
	}

	$main   = (int) get_post_thumbnail_id( $id );
	$photos = array_values( array_unique( array_filter( array_map( 'intval', (array) get_field( 'photos', $id ) ) ) ) );
	// The listing row: the main image, then the photographs, without repeats.
	$images = array_slice( array_values( array_unique( array_filter( array_merge( array( $main ), $photos ) ) ) ), 0, 3 );

	$summary = trim( (string) get_field( 'summary', $id ) );
	$lede    = trim( (string) get_field( 'lede', $id ) );
	$address = trim( (string) get_field( 'address', $id ) );
	$map     = trim( (string) get_field( 'map_url', $id ) );
	if ( ! $map && $address ) {
		$map = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address );
	}

	$register = (string) get_field( 'register_link', $id );
	if ( ! $register ) {
		$contact  = panmotors_contact_page();
		$register = $contact ? (string) get_permalink( $contact ) : '';
	}
	$label = trim( (string) get_field( 'register_label', $id ) );

	return array(
		'id'         => $id,
		'title'      => get_the_title( $id ),
		'url'        => (string) get_permalink( $id ),
		'type'       => trim( (string) get_field( 'event_type', $id ) ),
		'start'      => $start,
		'end_day'    => $end_day,
		'until'      => $until,
		'all_day'    => $all_day,
		'start_time' => $t_start ? $start->format( 'H:i' ) : '',
		'end_time'   => $t_end ? substr( $t_end, 0, 5 ) : '',
		'place'      => trim( (string) get_field( 'place', $id ) ),
		'address'    => $address,
		'map'        => $map,
		'guests'     => trim( (string) get_field( 'guests', $id ) ),
		'summary'    => $summary,
		'lede'       => $lede ? $lede : $summary,
		'status'     => $status,
		'past'       => $past,
		'pill'       => $pill,
		// Register, call and calendar: only while the event is ahead and goes ahead as planned.
		'buttons'    => ! $past && 'scheduled' === $status,
		'register'   => array(
			'label' => $label ? $label : __( 'Register interest', 'panmotors' ),
			'url'   => $register,
		),
		'show_call'  => null === get_field( 'show_call', $id ) ? true : (bool) get_field( 'show_call', $id ),
		'main'       => $main,
		'photos'     => $photos,
		'images'     => $images,
	);
}

/**
 * Published events as rows, split into upcoming (soonest first) and past (newest first).
 *
 * @return array { upcoming: array[], past: array[], all: array[] (by start date, oldest first) }
 */
function panmotors_events() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$rows = array();
	$ids  = get_posts(
		array(
			'post_type'              => 'pm_event',
			'post_status'            => 'publish',
			'posts_per_page'         => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Every event, for the tabs, the neighbours and the schema; a boutique has dozens, not thousands.
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);
	foreach ( $ids as $id ) {
		$row = panmotors_event( $id );
		if ( $row ) {
			$rows[] = $row;
		}
	}
	usort( $rows, static fn( $a, $b ) => $a['start'] == $b['start'] ? $a['id'] <=> $b['id'] : $a['start'] <=> $b['start'] ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- DateTimeImmutable compares by value.

	$cache = array(
		'all'      => $rows,
		'upcoming' => array_values( array_filter( $rows, static fn( $e ) => ! $e['past'] ) ),
		'past'     => array_reverse( array_values( array_filter( $rows, static fn( $e ) => $e['past'] ) ) ),
	);
	return $cache;
}

/**
 * The event's date for people: "17 October 2026", "17 – 19 October 2026",
 * "30 October – 2 November 2026", "30 December 2026 – 2 January 2027", each date in a <time>.
 *
 * @param array $event Row from panmotors_event().
 * @return string HTML.
 */
function panmotors_event_date_html( $event ) {
	$start = $event['start'];
	$end   = $event['end_day'];
	$time  = static fn( DateTimeImmutable $d, $format ) => sprintf( '<time datetime="%s">%s</time>', esc_attr( $d->format( 'Y-m-d' ) ), esc_html( wp_date( $format, $d->getTimestamp(), $d->getTimezone() ) ) );

	if ( ! $end || $end->format( 'Ymd' ) === $start->format( 'Ymd' ) ) {
		return $time( $start, 'd F Y' );
	}
	if ( $end->format( 'Y' ) !== $start->format( 'Y' ) ) {
		return $time( $start, 'd F Y' ) . ' &ndash; ' . $time( $end, 'd F Y' );
	}
	if ( $end->format( 'm' ) !== $start->format( 'm' ) ) {
		return $time( $start, 'd F' ) . ' &ndash; ' . $time( $end, 'd F Y' );
	}
	return $time( $start, 'd' ) . ' &ndash; ' . $time( $end, 'd F Y' );
}

/**
 * The event's times for people: "19:30 — 23:00", "19:30", "All day", or '' when none are set.
 *
 * @param array $event Row from panmotors_event().
 * @return string HTML.
 */
function panmotors_event_time_html( $event ) {
	if ( $event['all_day'] ) {
		return esc_html( panmotors_event_label( 'all_day', __( 'All day', 'panmotors' ) ) );
	}
	$parts = array();
	foreach ( array( $event['start_time'], $event['end_time'] ) as $t ) {
		if ( $t ) {
			$parts[] = sprintf( '<time datetime="%1$s">%1$s</time>', esc_html( $t ) );
		}
	}
	return implode( ' &mdash; ', $parts );
}

/**
 * Previous and next events in date order, wrapping around. None when there is only one event.
 *
 * @param int $id Event ID.
 * @return array { prev: array|null, next: array|null }
 */
function panmotors_event_neighbours( $id ) {
	$all = panmotors_events()['all'];
	$n   = count( $all );
	$at  = array_search( (int) $id, array_column( $all, 'id' ), true );
	if ( $n < 2 || false === $at ) {
		return array(
			'prev' => null,
			'next' => null,
		);
	}
	return array(
		'prev' => $all[ ( $at - 1 + $n ) % $n ],
		'next' => $all[ ( $at + 1 ) % $n ],
	);
}

/*
 * ------------------------------------------------------------------
 * Front end
 * ------------------------------------------------------------------
 */

/**
 * The Events page link in the menus is the current item on an event page (accent, aria-current),
 * as in the design.
 *
 * @param array   $atts Link attributes.
 * @param WP_Post $item Menu item.
 * @return array
 */
function panmotors_events_menu_current( $atts, $item ) {
	if ( is_singular( 'pm_event' ) && 'page' === $item->object && panmotors_events_page() === (int) $item->object_id ) {
		$atts['aria-current'] = 'page';
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'panmotors_events_menu_current', 10, 2 );

/**
 * Meta description of an event page without an SEO plugin description: its summary.
 *
 * @return string
 */
function panmotors_event_description() {
	$event = is_singular( 'pm_event' ) ? panmotors_event( get_queried_object_id() ) : null;
	return $event ? wp_strip_all_tags( $event['summary'] ) : '';
}

/**
 * Preload the event's main image (the header photo, the page's LCP image), with the same srcset
 * and sizes as the <img>.
 */
function panmotors_event_preload() {
	if ( ! is_singular( 'pm_event' ) ) {
		return;
	}
	$image_id = (int) get_post_thumbnail_id( get_queried_object_id() );
	$src      = $image_id ? wp_get_attachment_image_src( $image_id, 'pm-hero' ) : false;
	if ( $src ) {
		printf(
			'<link rel="preload" as="image" href="%s" imagesrcset="%s" imagesizes="100vw" fetchpriority="high">' . "\n",
			esc_url( $src[0] ),
			esc_attr( (string) wp_get_attachment_image_srcset( $image_id, 'pm-hero' ) )
		);
	}
}
add_action( 'wp_head', 'panmotors_event_preload', 1 );

/*
 * ------------------------------------------------------------------
 * Calendar file (.ics)
 * ------------------------------------------------------------------
 */

/**
 * The "Add to calendar" address of an event.
 *
 * @param array $event Row from panmotors_event().
 * @return string
 */
function panmotors_event_ics_url( $event ) {
	return add_query_arg( 'ics', '1', $event['url'] );
}

/**
 * Register the ics query var.
 *
 * @param string[] $vars Query vars.
 * @return string[]
 */
function panmotors_events_query_vars( $vars ) {
	$vars[] = 'ics';
	return $vars;
}
add_filter( 'query_vars', 'panmotors_events_query_vars' );

/**
 * One iCalendar line: text escaped (RFC 5545 §3.3.11) and folded at 75 octets, CRLF.
 *
 * @param string $name  Property with parameters, e.g. 'SUMMARY' or 'DTSTART;VALUE=DATE'.
 * @param string $value Value.
 * @param bool   $text  Escape as TEXT.
 * @return string
 */
function panmotors_ics_line( $name, $value, $text = false ) {
	if ( $text ) {
		$value = str_replace( array( '\\', ';', ',', "\r\n", "\n", "\r" ), array( '\\\\', '\;', '\,', '\n', '\n', '\n' ), $value );
	}
	$line = $name . ':' . $value;
	$out  = '';
	$size = strlen( $line );
	while ( $size > 75 ) {
		$cut = 75;
		// Never split a UTF-8 character.
		while ( $cut > 0 && ( ord( $line[ $cut ] ) & 0xC0 ) === 0x80 ) {
			--$cut;
		}
		$out .= substr( $line, 0, $cut ) . "\r\n ";
		$line = substr( $line, $cut );
		$size = strlen( $line );
	}
	return $out . $line . "\r\n";
}

/**
 * The event as an iCalendar file. Timed events in UTC, all-day and untimed events as dates
 * (the end date is the day after the last day, as the format asks).
 *
 * @param array $event Row from panmotors_event().
 * @return string
 */
function panmotors_event_ics( $event ) {
	$utc      = new DateTimeZone( 'UTC' );
	$location = implode( ', ', array_filter( array( $event['place'], $event['address'] ) ) );
	$lines    = panmotors_ics_line( 'BEGIN', 'VCALENDAR' )
		. panmotors_ics_line( 'VERSION', '2.0' )
		. panmotors_ics_line( 'PRODID', '-//' . wp_parse_url( home_url(), PHP_URL_HOST ) . '//Pan Motors events//EN' )
		. panmotors_ics_line( 'CALSCALE', 'GREGORIAN' )
		. panmotors_ics_line( 'METHOD', 'PUBLISH' )
		. panmotors_ics_line( 'BEGIN', 'VEVENT' )
		. panmotors_ics_line( 'UID', 'event-' . $event['id'] . '@' . wp_parse_url( home_url(), PHP_URL_HOST ) )
		. panmotors_ics_line( 'DTSTAMP', gmdate( 'Ymd\THis\Z', (int) get_post_modified_time( 'U', true, $event['id'] ) ) );

	if ( $event['start_time'] ) {
		$lines .= panmotors_ics_line( 'DTSTART', $event['start']->setTimezone( $utc )->format( 'Ymd\THis\Z' ) )
			. panmotors_ics_line( 'DTEND', $event['until']->setTimezone( $utc )->format( 'Ymd\THis\Z' ) );
	} else {
		$last   = $event['end_day'] ? $event['end_day'] : $event['start'];
		$lines .= panmotors_ics_line( 'DTSTART;VALUE=DATE', $event['start']->format( 'Ymd' ) )
			. panmotors_ics_line( 'DTEND;VALUE=DATE', $last->modify( '+1 day' )->format( 'Ymd' ) );
	}

	$lines .= panmotors_ics_line( 'SUMMARY', html_entity_decode( $event['title'], ENT_QUOTES, 'UTF-8' ), true );
	if ( $event['summary'] ) {
		$lines .= panmotors_ics_line( 'DESCRIPTION', $event['summary'], true );
	}
	if ( $location ) {
		$lines .= panmotors_ics_line( 'LOCATION', $location, true );
	}
	$lines .= panmotors_ics_line( 'URL', $event['url'] )
		. panmotors_ics_line( 'STATUS', 'cancelled' === $event['status'] ? 'CANCELLED' : 'CONFIRMED' )
		. panmotors_ics_line( 'END', 'VEVENT' )
		. panmotors_ics_line( 'END', 'VCALENDAR' );
	return $lines;
}

/**
 * Answer /events/{slug}/?ics=1 with the calendar file.
 */
function panmotors_event_ics_serve() {
	if ( ! is_singular( 'pm_event' ) || ! get_query_var( 'ics' ) ) {
		return;
	}
	$event = panmotors_event( get_queried_object_id() );
	if ( ! $event ) {
		return;
	}
	nocache_headers();
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( get_post_field( 'post_name', $event['id'] ) ) . '.ics"' );
	header( 'X-Robots-Tag: noindex' );
	echo panmotors_event_ics( $event ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- iCalendar text, escaped by panmotors_ics_line().
	exit;
}
add_action( 'template_redirect', 'panmotors_event_ics_serve' );

/*
 * ------------------------------------------------------------------
 * wp-admin
 * ------------------------------------------------------------------
 */

/**
 * Title and main image are required to publish; the block editor shows this message.
 *
 * @param stdClass|WP_Error $prepared Post about to be saved.
 * @param WP_REST_Request   $request  Request.
 * @return stdClass|WP_Error
 */
function panmotors_event_rest_required( $prepared, $request ) {
	if ( is_wp_error( $prepared ) ) {
		return $prepared;
	}
	$id     = (int) ( $prepared->ID ?? 0 );
	$status = $prepared->post_status ?? ( $id ? get_post_status( $id ) : 'draft' );
	if ( ! in_array( $status, array( 'publish', 'future' ), true ) ) {
		return $prepared;
	}
	$title = isset( $prepared->post_title ) ? (string) $prepared->post_title : ( $id ? get_the_title( $id ) : '' );
	$image = $request->has_param( 'featured_media' ) ? (int) $request['featured_media'] : ( $id ? (int) get_post_thumbnail_id( $id ) : 0 );

	$missing = array();
	if ( '' === trim( $title ) ) {
		$missing[] = __( 'Add a title for the event.', 'panmotors' );
	}
	if ( ! $image ) {
		$missing[] = __( 'Set the main image (Event → Main image in the sidebar).', 'panmotors' );
	}
	if ( $missing ) {
		return new WP_Error( 'pm_event_required', implode( ' ', $missing ), array( 'status' => 400 ) );
	}
	return $prepared;
}
add_filter( 'rest_pre_insert_pm_event', 'panmotors_event_rest_required', 10, 2 );

/**
 * The end date cannot be before the start date (Event details).
 *
 * @param bool|string $valid Valid, or an error message.
 * @param mixed       $value End date (Ymd).
 * @return bool|string
 */
function panmotors_event_validate_end( $valid, $value ) {
	if ( true !== $valid || '' === (string) $value ) {
		return $valid;
	}
	$start = isset( $_POST['acf']['field_pm_event_start_date'] ) ? preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['acf']['field_pm_event_start_date'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF verified the nonce before validating.
	if ( $start && strcmp( preg_replace( '/\D/', '', (string) $value ), $start ) < 0 ) {
		return __( 'The end date is before the start date. Choose a later end date, or leave it empty for a one-day event.', 'panmotors' );
	}
	return $valid;
}
add_filter( 'acf/validate_value/key=field_pm_event_end_date', 'panmotors_event_validate_end', 10, 2 );

/**
 * Main image size hint under the Main image panel of the block editor, and the screen's styles.
 */
function panmotors_event_editor_assets() {
	$screen = get_current_screen();
	if ( ! $screen || 'pm_event' !== $screen->post_type ) {
		return;
	}
	$js = 'assets/js/event-editor.js';
	wp_enqueue_script( 'pm-event-editor', PANMOTORS_URI . '/' . $js, array( 'wp-hooks', 'wp-element', 'wp-dom-ready', 'wp-data', 'wp-preferences' ), panmotors_asset_version( $js ), true );
	wp_localize_script(
		'pm-event-editor',
		'pmEventEditor',
		array(
			'hint' => __( 'Required. The photo at the top of the event page, the first photo on the Events page and the image shared on social media. Landscape, best at 2400px wide or larger, JPG or WebP, sRGB colour. Smaller files still upload.', 'panmotors' ),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'panmotors_event_editor_assets' );

/**
 * In the editor canvas, the event's title reads as its heading (it is the event page's H1), and
 * the story sits in the column it has on the page.
 */
function panmotors_event_canvas_styles() {
	if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'pm_event' !== $screen->post_type ) {
		return;
	}
	wp_add_inline_style(
		'pm-editor',
		'.editor-styles-wrapper .editor-post-title{padding:56px var(--pad-x) 32px;border:0;font-family:var(--font-display);font-size:calc(clamp(40px,4.4vw,72px) * var(--h-scale));font-weight:400;letter-spacing:-.01em;line-height:1;text-transform:none;opacity:1}'
		. '.editor-styles-wrapper .is-root-container>.wp-block:not([data-type^="pm/"],[data-type="core/block"]){max-width:62ch;margin-left:0;font-size:var(--fs-body);line-height:1.85}'
	);
}
add_action( 'enqueue_block_assets', 'panmotors_event_canvas_styles', 20 );

/**
 * Events list columns: event, date, type, place, status.
 *
 * @param array $columns Columns.
 * @return array
 */
function panmotors_events_columns( $columns ) {
	return array(
		'cb'        => $columns['cb'],
		'title'     => __( 'Event', 'panmotors' ),
		'pm_date'   => __( 'Event date', 'panmotors' ),
		'pm_type'   => __( 'Type', 'panmotors' ),
		'pm_place'  => __( 'Place', 'panmotors' ),
		'pm_status' => __( 'Status', 'panmotors' ),
	);
}
add_filter( 'manage_pm_event_posts_columns', 'panmotors_events_columns' );

/**
 * Print the custom column values.
 *
 * @param string $column  Column key.
 * @param int    $post_id Event ID.
 */
function panmotors_events_column( $column, $post_id ) {
	$event = panmotors_event( $post_id );
	if ( ! $event ) {
		if ( 'pm_date' === $column ) {
			echo '<span class="pm-admin-missing">' . esc_html__( 'No start date', 'panmotors' ) . '</span>';
		}
		return;
	}
	if ( 'pm_date' === $column ) {
		echo wp_kses( panmotors_event_date_html( $event ), array( 'time' => array( 'datetime' => true ) ) );
		$times = panmotors_event_time_html( $event );
		if ( $times ) {
			echo '<br><span class="pm-admin-sub">' . wp_kses( $times, array( 'time' => array( 'datetime' => true ) ) ) . '</span>';
		}
	} elseif ( 'pm_type' === $column ) {
		echo esc_html( $event['type'] );
	} elseif ( 'pm_place' === $column ) {
		echo esc_html( $event['place'] );
	} elseif ( 'pm_status' === $column ) {
		printf( '<span class="pm-admin-status pm-admin-status--%s">%s</span>', esc_attr( $event['buttons'] ? 'on' : 'off' ), esc_html( $event['pill'] ) );
	}
}
add_action( 'manage_pm_event_posts_custom_column', 'panmotors_events_column', 10, 2 );

/**
 * The Event date column sorts.
 *
 * @param array $columns Sortable columns.
 * @return array
 */
function panmotors_events_sortable( $columns ) {
	$columns['pm_date'] = 'pm_date';
	return $columns;
}
add_filter( 'manage_edit-pm_event_sortable_columns', 'panmotors_events_sortable' );

/**
 * Events list sorted by start date (latest first, so the coming events lead), unless the client
 * sorts by another column.
 *
 * @param WP_Query $query Query.
 */
function panmotors_events_admin_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'pm_event' !== $query->get( 'post_type' ) ) {
		return;
	}
	$orderby = $query->get( 'orderby' );
	if ( $orderby && 'pm_date' !== $orderby ) {
		return;
	}
	$query->set( 'meta_key', 'start_date' );
	$query->set( 'orderby', 'meta_value' );
	$query->set( 'order', 'pm_date' === $orderby ? $query->get( 'order' ) : 'DESC' );
}
add_action( 'pre_get_posts', 'panmotors_events_admin_order' );

/**
 * Column widths and status badges.
 */
function panmotors_events_admin_css() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-pm_event' !== $screen->id ) {
		return;
	}
	echo '<style>.column-pm_date{width:190px}.column-pm_type,.column-pm_status{width:120px}.pm-admin-sub{color:#646970}.pm-admin-missing{color:#b32d2e}.pm-admin-status{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;letter-spacing:.04em;background:#dcdcde;color:#1d2327}.pm-admin-status--on{background:' . esc_attr( panmotors_design()['accent'] ) . ';color:#fff}</style>';
}
add_action( 'admin_head', 'panmotors_events_admin_css' );
