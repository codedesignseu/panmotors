<?php
/**
 * Shared helpers for the dev scripts: the seed (dev/seed.php) and the migrations
 * (dev/migrations/). Development only, excluded from deploys via .distignore.
 *
 * - panmotors_dev_backup(): database export to dev/.cache/db/ before anything is written.
 * - panmotors_seed_created(): the registry of what the seed has created, so it never recreates
 *   something the client deleted.
 * - panmotors_migration_begin() / _done(): run each migration once.
 * - Block markup builders (panmotors_seed_block() and friends).
 *
 * @package panmotors
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Development only: run through WP-CLI.\n" );
}

/**
 * Export the database to dev/.cache/db/<label>-<time>.sql. Stops the script if the export fails,
 * so nothing is written without a backup. Keeps the newest 20 exports.
 *
 * @param string $label File name prefix, e.g. 'seed'.
 * @return string Path of the export.
 */
function panmotors_dev_backup( $label ) {
	$dir = dirname( __DIR__ ) . '/dev/.cache/db';
	wp_mkdir_p( $dir );
	$file = $dir . '/' . sanitize_file_name( $label ) . '-' . gmdate( 'Ymd-His' ) . '.sql';

	// Local by Flywheel: the database listens on a socket that only PHP's ini knows about.
	$socket = (string) ini_get( 'mysqli.default_socket' );
	$cmd    = 'db export ' . escapeshellarg( $file ) . ( $socket && file_exists( $socket ) ? ' --socket=' . escapeshellarg( $socket ) : '' );
	$result = WP_CLI::runcommand(
		$cmd,
		array(
			'return'     => 'all',
			'exit_error' => false,
			'launch'     => true,
		)
	);
	if ( $result->return_code || ! file_exists( $file ) || filesize( $file ) < 1024 ) {
		WP_CLI::error( "Database export failed, nothing was changed.\n" . $result->stderr );
	}

	$old = glob( $dir . '/*.sql' );
	sort( $old );
	foreach ( array_slice( $old, 0, max( 0, count( $old ) - 20 ) ) as $gone ) {
		unlink( $gone ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
	}

	WP_CLI::log( 'Database exported to ' . str_replace( dirname( __DIR__ ) . '/', '', $file ) );
	return $file;
}

/**
 * Whether the seed runs with --reset-demo (overwrite demo content).
 *
 * @return bool
 */
function panmotors_seed_reset() {
	return defined( 'PANMOTORS_SEED_RESET' ) && PANMOTORS_SEED_RESET;
}

/**
 * Registry of what the seed has created (option panmotors_seed_created). A key in it is never
 * created again, so demo content the client deleted stays deleted. --reset-demo ignores it.
 *
 * @param string $key  e.g. 'car:mclaren', 'page:about', 'media:<file>'.
 * @param bool   $mark Record the key.
 * @return bool Whether the key was recorded before this call.
 */
function panmotors_seed_created( $key, $mark = false ) {
	$done = (array) get_option( 'panmotors_seed_created', array() );
	$was  = isset( $done[ $key ] );
	if ( $mark && ! $was ) {
		$done[ $key ] = gmdate( 'c' );
		update_option( 'panmotors_seed_created', $done, false );
	}
	return $was;
}

/**
 * Start a one-off migration: stops when it already ran, else exports the database.
 *
 * @param string $id Migration id (its file name without .php).
 * @return bool False when it already ran.
 */
function panmotors_migration_begin( $id ) {
	if ( 1 !== get_current_user_id() ) {
		WP_CLI::error( 'Run migrations as the administrator: --user=1' );
	}
	$done = (array) get_option( 'panmotors_migrations', array() );
	if ( isset( $done[ $id ] ) ) {
		WP_CLI::log( "Migration {$id} already ran on {$done[ $id ]}. Nothing to do." );
		return false;
	}
	panmotors_dev_backup( 'migration-' . $id );
	return true;
}

/**
 * Record a migration as done.
 *
 * @param string $id Migration id.
 */
function panmotors_migration_done( $id ) {
	$done        = (array) get_option( 'panmotors_migrations', array() );
	$done[ $id ] = gmdate( 'c' );
	update_option( 'panmotors_migrations', $done, false );
	WP_CLI::success( "Migration {$id} done." );
}

/**
 * Attachment imported by the seed from _design/uploads, by its original file name.
 *
 * @param string $original File name in _design/uploads.
 * @return int Attachment ID, or 0.
 */
function panmotors_dev_media( $original ) {
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_pm_seed_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $original, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * ID of a published page by slug, or 0. For link fields in demo content only.
 *
 * @param string $slug Page slug.
 * @return int
 */
function panmotors_dev_page( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? (int) $page->ID : 0;
}

/**
 * ACF block data for a pm/* block, from field names. Field keys come from the block's field
 * group (acf-json), so seed data never repeats them. Repeaters are flattened the way ACF stores
 * them: rows_0_sub, and the row count under the repeater name.
 *
 * @param string $block  Block name, e.g. 'pm/hero'.
 * @param array  $values Field name => value.
 * @return array Block attributes.
 */
function panmotors_seed_block_attrs( $block, $values ) {
	$group  = 'group_pm_block_' . str_replace( '-', '_', substr( $block, 3 ) );
	$fields = array();
	foreach ( acf_get_fields( $group ) as $field ) {
		$fields[ $field['name'] ] = $field;
	}

	$data    = array();
	$flatten = static function ( $prefix, $field, $value ) use ( &$data, &$flatten ) {
		if ( 'repeater' === $field['type'] ) {
			$subs = array_column( $field['sub_fields'], null, 'name' );
			foreach ( array_values( (array) $value ) as $i => $row ) {
				foreach ( $row as $name => $sub_value ) {
					if ( isset( $subs[ $name ] ) ) {
						$flatten( "{$prefix}_{$i}_{$name}", $subs[ $name ], $sub_value );
					}
				}
			}
			$data[ $prefix ]       = count( (array) $value );
			$data[ '_' . $prefix ] = $field['key'];
			return;
		}
		$data[ $prefix ]       = is_array( $value ) ? array_map( 'strval', $value ) : $value;
		$data[ '_' . $prefix ] = $field['key'];
	};

	foreach ( $values as $name => $value ) {
		if ( ! isset( $fields[ $name ] ) ) {
			WP_CLI::error( "Seed: no field '{$name}' in block {$block}." );
		}
		$flatten( $name, $fields[ $name ], $value );
	}

	return array(
		'name' => $block,
		'data' => $data,
		'mode' => 'preview',
	);
}

/**
 * One pm/* block as markup.
 *
 * @param string $block  Block name.
 * @param array  $values Field name => value.
 * @param array  $extra  Extra attributes (e.g. lock).
 * @return string
 */
function panmotors_seed_block( $block, $values = array(), $extra = array() ) {
	return serialize_block(
		array(
			'blockName'    => $block,
			'attrs'        => array_merge( panmotors_seed_block_attrs( $block, $values ), $extra ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	);
}

/**
 * Core paragraphs as block markup, from simple HTML paragraphs.
 *
 * @param string $html One or more <p> elements.
 * @return string
 */
function panmotors_seed_paragraphs( $html ) {
	preg_match_all( '#<p>(.*?)</p>#s', $html, $m );
	return implode( "\n\n", array_map( static fn( $p ) => "<!-- wp:paragraph -->\n<p>{$p}</p>\n<!-- /wp:paragraph -->", $m[1] ) );
}


/**
 * About page as block markup (D12, _design/v2/about.html): page header (photo), story, What We Do,
 * Our Values (light section), photo call to action. Used by the seed and by the migration that
 * brings an existing About page to this layout. Photos are the seed's imports; links go to the
 * Showroom and Contact pages.
 *
 * @return string
 */
function panmotors_demo_about_content() {
	return implode(
		"\n\n",
		array(
			panmotors_seed_block(
				'pm/page-header',
				array(
					'header_style'      => 'image',
					'header_eyebrow'    => 'About — Mesoyi, Paphos',
					'header_title'      => "About\nPan Motors",
					'header_intro'      => 'Luxury in Motion. A family-run car house on Avenue 65.',
					'header_image'      => panmotors_dev_media( 'DSC08440-copy-Large.jpg' ),
					'header_filter'     => 'grayscale',
					'header_brightness' => 60,
				)
			),
			panmotors_seed_block(
				'pm/story',
				array(
					'story_eyebrow' => 'Our story',
					'story_title'   => "One Roof,\nOne Family",
					'story_lead'    => '', // The one-sentence description from the options.
					'story_text'    => '<p>Sales, service and a boutique sit under one roof, so a car is prepared, presented and looked after by the same people who sold it.</p><p>Every car on the floor is chosen, inspected and prepared in house before it is shown.</p>',
					'story_image'   => panmotors_dev_media( 'IMG_6844-scaled.jpg' ),
				)
			),
			panmotors_seed_block(
				'pm/services',
				array(
					'services_title' => 'What We Do',
					'services'       => array(
						array(
							'index' => '01 — Sales',
							'title' => 'Sales',
							'body'  => 'Luxury and performance cars, chosen and prepared before they reach the floor.',
							'image' => panmotors_dev_media( 'black-porsche-911-luxury-sports-car-with-glossy-reflections-studio-lighting-generative-ai.jpg' ),
						),
						array(
							'index' => '02 — Service',
							'title' => 'Service',
							'body'  => 'Aftercare in house, by the people who know the car.',
							'image' => panmotors_dev_media( 'DSC08476-copy-Large.jpg' ),
						),
						array(
							'index' => '03 — Boutique',
							'title' => 'Boutique',
							'body'  => 'Parts, accessories and details for the car and the driver.',
							'image' => panmotors_dev_media( 'sleek-black-sports-car-dramatic-lighting.jpg' ),
						),
					),
				)
			),
			panmotors_seed_block(
				'pm/values',
				array(
					'values_style' => 'light',
					'values_title' => 'Our Values',
					'values_intro' => 'Four things we hold to with every car and every client.',
				)
			),
			panmotors_seed_block(
				'pm/cta-image',
				array(
					'ctai_eyebrow' => 'Avenue 65, Mesoyi',
					'ctai_title'   => "See The\nShowroom",
					'ctai_image'   => panmotors_dev_media( 'DSC04357-copy-scaled.jpg' ),
					'ctai_label'   => 'Visit the showroom',
					'ctai_link'    => panmotors_dev_page( 'showroom' ),
					'ctai_label_2' => 'Book a visit',
					'ctai_link_2'  => panmotors_dev_page( 'contact' ),
				)
			),
		)
	);
}

/**
 * Featured Cars page as block markup (D12, _design/v2/cars.html): page header (text), cars grid,
 * CTA band. Used by the seed and by the migration that brings an existing page to this layout.
 *
 * @return string
 */
function panmotors_demo_featured_content() {
	$contact = panmotors_dev_page( 'contact' );
	return implode(
		"\n\n",
		array(
			panmotors_seed_block(
				'pm/page-header',
				array(
					'header_style'   => 'text',
					'header_eyebrow' => 'Pan Motors — Paphos',
					'header_title'   => "Featured\nCars",
					'header_intro'   => 'Twelve cars currently on the floor at Avenue 65. Hover to open a car, click for the full sheet.',
				)
			),
			panmotors_seed_block(
				'pm/cars-grid',
				array(
					'cars_source'        => 'all',
					'cars_intro'         => '',
					'cars_all_label'     => 'All',
					'cars_enquire_label' => 'Enquire',
					'cars_enquire_link'  => $contact,
					'cars_label_year'    => 'Year',
					'cars_label_engine'  => 'Engine',
					'cars_label_power'   => 'Power',
					'cars_label_sprint'  => 'Acceleration',
					'cars_label_gearbox' => 'Gearbox',
					'cars_label_colour'  => 'Colour',
					'cars_label_mileage' => 'Mileage',
					'cars_label_no'      => 'No.',
				)
			),
			panmotors_seed_block(
				'pm/cta-band',
				array(
					'cta_title' => "Seen One\nYou Like?",
					'cta_text'  => '',
					'cta_label' => 'Arrange a viewing',
					'cta_link'  => $contact,
				)
			),
		)
	);
}

/**
 * Showroom page as block markup (D12, _design/v2/showroom.html): page header (photo), photo slider
 * ("Inside"), visit (hours and address from the options), photo call to action. Used by the seed
 * and by the migration that brings an existing page to this layout.
 *
 * @return string
 */
function panmotors_demo_showroom_content() {
	return implode(
		"\n\n",
		array(
			panmotors_seed_block(
				'pm/page-header',
				array(
					'header_style'      => 'image',
					'header_eyebrow'    => 'Avenue 65, Mesoyi — Paphos',
					'header_title'      => "The\nShowroom",
					'header_intro'      => 'Paphos, open six days a week. Sales, service and the boutique under one roof.',
					'header_image'      => panmotors_dev_media( 'DSC04357-copy-scaled.jpg' ),
					'header_filter'     => 'none',
					'header_brightness' => 62,
				)
			),
			panmotors_seed_block(
				'pm/photo-slider',
				array(
					'slider_title'  => 'Inside',
					'slider_hint'   => 'Drag or use arrows',
					'slider_photos' => array_filter(
						array(
							panmotors_dev_media( 'DSC04357-copy-scaled.jpg' ),
							panmotors_dev_media( 'IMG_6844-scaled.jpg' ),
							panmotors_dev_media( 'DSC08440-copy-Large.jpg' ),
							panmotors_dev_media( 'DSC08476-copy-Large.jpg' ),
						)
					),
				)
			),
			panmotors_seed_block(
				'pm/visit',
				array(
					'visit_hours_label'      => 'Opening hours',
					'visit_find_label'       => 'Find us',
					'visit_directions_label' => 'Get directions',
				)
			),
			panmotors_seed_block(
				'pm/cta-image',
				array(
					'ctai_eyebrow'    => '',
					'ctai_title'      => "Book\nA Visit",
					'ctai_image'      => panmotors_dev_media( 'IMG_6844-scaled.jpg' ),
					'ctai_filter'     => 'none',
					'ctai_brightness' => 50,
					'ctai_size'       => 'standard',
					'ctai_label'      => 'Contact us',
					'ctai_link'       => panmotors_dev_page( 'contact' ),
					'ctai_label_2'    => 'See featured cars',
					'ctai_link_2'     => panmotors_dev_page( 'featured-cars' ),
				)
			),
		)
	);
}

/**
 * Contact page as block markup (D12, _design/v2/contact.html): page header (text), contact rows,
 * contact form and map, questions. The questions are passed in, so the migration keeps the page's
 * current ones word for word.
 *
 * @param string $faq_block The pm/faq block markup (the existing one, or the seed's).
 * @return string
 */
function panmotors_demo_contact_content( $faq_block ) {
	return implode(
		"\n\n",
		array(
			panmotors_seed_block(
				'pm/page-header',
				array(
					'header_style'   => 'text',
					'header_eyebrow' => 'Pan Motors — Paphos, Cyprus',
					'header_title'   => "Contact\nUs",
					'header_intro'   => 'Call, write, or walk in during showroom hours.',
				)
			),
			panmotors_seed_block(
				'pm/contact-details',
				array(
					'cd_call_label'    => 'Call us',
					'cd_call_action'   => 'Call',
					'cd_email_label'   => 'Email us',
					'cd_email_action'  => 'Write',
					'cd_find_label'    => 'Find us',
					'cd_find_action'   => 'Directions',
					'cd_follow_label'  => 'Follow us',
					'cd_follow_action' => 'Open',
				)
			),
			panmotors_seed_block(
				'pm/contact-form',
				array(
					'cf_title'         => 'Write To Us',
					'cf_shortcode'     => '',
					'cf_label_name'    => 'Name',
					'cf_hint_name'     => 'Full name',
					'cf_label_email'   => 'Email',
					'cf_hint_email'    => 'you@domain.com',
					'cf_label_subject' => 'Subject',
					'cf_hint_subject'  => 'Viewing, service, boutique',
					'cf_label_message' => 'Message',
					'cf_hint_message'  => 'Tell us which car you are interested in.',
					'cf_button'        => 'Send message',
					'cf_map_note'      => 'Google Maps',
					'cf_map_button'    => 'Show map',
					'cf_map_link'      => 'Get directions',
					'cf_hours_label'   => 'Showroom hours',
				)
			),
			$faq_block,
		)
	);
}

/**
 * Events page as block markup (_design/events/events.html): page header (text), events list,
 * CTA band "Join The / Guest List" to Contact. Used by the seed and by the migration that creates
 * the page on an existing site.
 *
 * @return string
 */
function panmotors_demo_events_page_content() {
	$contact = panmotors_dev_page( 'contact' );
	return implode(
		"\n\n",
		array(
			panmotors_seed_block(
				'pm/page-header',
				array(
					'header_style'   => 'text',
					'header_eyebrow' => 'Pan Motors — Paphos',
					'header_title'   => 'Events',
					'header_intro'   => 'Evenings in the showroom, drives along the coast and new arrivals shown for the first time.',
				)
			),
			panmotors_seed_block(
				'pm/events-list',
				array(
					'events_intro'        => '',
					'events_tab_upcoming' => 'Upcoming',
					'events_tab_past'     => 'Past',
					'events_view_label'   => 'View event',
					'events_show_past'    => 1,
					'events_past_limit'   => '',
					'events_empty_text'   => 'No events are planned just now. Leave your details and we will write when the next one opens.',
					'events_empty_label'  => 'Contact us',
					'events_empty_link'   => $contact,
				)
			),
			panmotors_seed_block(
				'pm/cta-band',
				array(
					'cta_title' => "Join The\nGuest List",
					'cta_text'  => 'Places are limited. Leave your details and we will write when the next event opens.',
					'cta_label' => 'Contact us',
					'cta_link'  => $contact,
				)
			),
		)
	);
}

/**
 * The five demo events of _design/events/ (events.html and event.html), word for word. Dates are
 * fixed: after 5 December 2026 every demo event counts as past (docs/events.md).
 * Photos are the seed's imports of the same files (_design/uploads), by original file name: the
 * first is the main image, all four are the Photographs.
 *
 * @return array[] Keyed by seed key.
 */
function panmotors_demo_events() {
	return array(
		'night-at-avenue-65'   => array(
			'title'   => 'Night at Avenue 65',
			'type'    => 'Evening',
			'date'    => '20261017',
			'start'   => '19:30:00',
			'end'     => '23:00:00',
			'place'   => 'Showroom, Mesoyi',
			'guests'  => 'By invitation',
			'summary' => 'An evening in the showroom after hours. The floor lit low, the new arrivals out, and time to talk through the cars with the family.',
			'lede'    => 'An evening in the showroom after hours, with the floor lit low and the new arrivals out.',
			'story'   => array(
				'The doors close to the public at six and open again at half past seven for guests of the house. The cars stay where they are; the lights come down, and there is time to look properly.',
				'The family will be on the floor through the evening to talk through each car, its history and how it has been prepared. Drinks and a light supper are served in the boutique.',
				'Places are limited. Register your interest and we will confirm by phone.',
			),
			'photos'  => array( 'DSC04357-copy-scaled.jpg', 'IMG_6844-scaled.jpg', 'DSC08440-copy-Large.jpg', 'DSC08476-copy-Large.jpg' ),
		),
		'west-coast-drive'     => array(
			'title'   => 'West Coast Drive',
			'type'    => 'Drive',
			'date'    => '20261108',
			'start'   => '08:00:00',
			'end'     => '14:00:00',
			'place'   => 'Paphos to Latchi',
			'guests'  => 'Owners and guests',
			'summary' => 'A morning convoy along the west coast, starting at the showroom and ending with lunch by the sea.',
			'lede'    => 'A morning convoy along the west coast, from the showroom to lunch by the sea.',
			'story'   => array(
				'We meet at the showroom for coffee at eight and leave at half past, heading north through Coral Bay and the Akamas road.',
				'The pace is relaxed and the route is chosen for the views. Lunch is booked at a harbour table in Latchi, and the return is at your own pace.',
				'Bring your own car, or ask us about joining as a passenger.',
			),
			'photos'  => array( 'red-sports-car-is-driving-empty-road-night-there-are-tall-buildings-background.jpg', 'sunset-supercar.jpg', 'black-porsche-911-luxury-sports-car-with-glossy-reflections-studio-lighting-generative-ai.jpg', 'sleek-black-sports-car-dramatic-lighting.jpg' ),
		),
		'winter-arrivals'      => array(
			'title'   => 'Winter Arrivals',
			'type'    => 'Unveiling',
			'date'    => '20261205',
			'start'   => '18:00:00',
			'end'     => '21:00:00',
			'place'   => 'Showroom, Mesoyi',
			'guests'  => 'By invitation',
			'summary' => 'The winter selection shown for the first time, before any of it goes on the floor.',
			'lede'    => 'The winter selection shown for the first time, before any of it goes on the floor.',
			'story'   => array(
				'Each season a small number of cars arrive together. This evening is the first time they are shown, under covers until the hour.',
				'Specifications, histories and viewing times are available on the night for anyone who wants to look closer.',
			),
			'photos'  => array( 'sleek-black-sports-car-dramatic-lighting.jpg', 'close-up-mclaren-720s-indoor-showroom-with-checkered-floor.jpg', 'black-sports-car-with-number-37-back.jpg', 'DSC08440-copy-Large.jpg' ),
		),
		'summer-cars-coffee'   => array(
			'title'   => 'Summer Cars & Coffee',
			'type'    => 'Morning',
			'date'    => '20260614',
			'start'   => '07:30:00',
			'end'     => '10:00:00',
			'place'   => 'Forecourt, Mesoyi',
			'guests'  => 'Open',
			'summary' => 'Owners and friends on the forecourt for an early coffee before the heat.',
			'lede'    => 'Owners and friends on the forecourt for an early coffee before the heat.',
			'story'   => array(
				'More than forty cars filled the forecourt and the road outside by eight. Coffee ran out twice.',
				'Thank you to everyone who came. The next morning meet will be announced here.',
			),
			'photos'  => array( 'DSC08476-copy-Large.jpg', 'sunset-supercar.jpg', 'IMG_6844-scaled.jpg', 'black-sports-car-with-number-37-back.jpg' ),
		),
		'spring-opening'       => array(
			'title'   => 'Spring Opening',
			'type'    => 'Evening',
			'date'    => '20260322',
			'start'   => '19:00:00',
			'end'     => '22:00:00',
			'place'   => 'Showroom, Mesoyi',
			'guests'  => 'By invitation',
			'summary' => 'The spring selection revealed on the showroom floor, with guests of the house.',
			'lede'    => 'The spring selection revealed on the showroom floor, with guests of the house.',
			'story'   => array(
				'Spring opened with the new selection on the floor and the boutique open late.',
				'Several of the cars shown that evening have since found new owners.',
			),
			'photos'  => array( 'DSC08440-copy-Large.jpg', 'black-porsche-911-luxury-sports-car-with-glossy-reflections-studio-lighting-generative-ai.jpg', 'DSC04357-copy-scaled.jpg', 'close-up-mclaren-720s-indoor-showroom-with-checkered-floor.jpg' ),
		),
	);
}

/**
 * Add a page link to a menu just before a given page's item, unless the page is already in it.
 * The items from there on move down one place. Used by migrations; the seed builds new menus whole.
 *
 * @param int    $menu_id     Menu (term) ID.
 * @param int    $page_id     Page to add.
 * @param string $label       Menu label.
 * @param int    $before_page Page whose item it goes before (0 or not in the menu: at the end).
 * @return bool Whether it was added.
 */
function panmotors_dev_menu_add( $menu_id, $page_id, $label, $before_page = 0 ) {
	$items = (array) wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
	foreach ( $items as $item ) {
		if ( 'page' === $item->object && (int) $item->object_id === (int) $page_id ) {
			return false;
		}
	}
	usort( $items, static fn( $a, $b ) => $a->menu_order <=> $b->menu_order );
	$position = count( $items ) + 1;
	foreach ( $items as $i => $item ) {
		if ( $before_page && 'page' === $item->object && (int) $item->object_id === (int) $before_page ) {
			$position = $i + 1;
			break;
		}
	}
	// Make room: every item from the new position on moves down one.
	foreach ( $items as $i => $item ) {
		$order = $i + 1 >= $position ? $i + 2 : $i + 1;
		if ( (int) $item->menu_order !== $order ) {
			wp_update_post(
				array(
					'ID'         => $item->ID,
					'menu_order' => $order,
				)
			);
		}
	}
	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'     => $label,
			'menu-item-object'    => 'page',
			'menu-item-object-id' => (int) $page_id,
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
			'menu-item-position'  => $position,
		)
	);
	return true;
}
