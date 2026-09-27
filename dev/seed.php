<?php
/**
 * Demo content seed. Development only, excluded from deploys via .distignore.
 *
 * Run from Local's site shell, in the theme folder, as the administrator (user 1), after
 * committing acf-json/:
 *   wp --require=dev/seed-command.php panmotors seed --user=1
 *   wp eval-file dev/seed.php --user=1            (the same, without the command)
 *
 * Safe by default: it only creates what is missing. It never overwrites or deletes existing
 * pages, field values, options, cars, menus or media, and never recreates demo content the client
 * deleted (registry: option panmotors_seed_created). It exports the database to dev/.cache/db/
 * before writing anything.
 *
 *   wp --require=dev/seed-command.php panmotors seed --user=1 --reset-demo
 * overwrites the demo content (items flagged _pm_demo) with the seed's version. It runs only when
 * WP_ENVIRONMENT_TYPE is local or development.
 *
 * Structural changes to existing content (a block added to a page, data moved) are one-off
 * scripts in dev/migrations/, never the seed.
 *
 * What it does (D11, docs/blocks.md):
 * - Updates the database copies of the field groups from acf-json, so they are editable in wp-admin,
 *   and removes the database copies of pm groups whose JSON is gone.
 * - Imports _design/uploads into the media library with descriptive file names, titles, alt text
 *   and captions (theme-map 9.5).
 * - Fills the Pan Motors options that were never saved with the business facts from the design.
 * - Creates the demo cars, the pages as block markup, Home, the legal pages and the menus.
 * - Sets a static front page, the site title, pretty permalinks and the custom logo, where unset.
 *
 * Copy marked DRAFT is for the client to confirm or replace.
 * Everything it creates is flagged with the `_pm_demo` meta / `panmotors_demo_content` option
 * so it can be found and removed before launch.
 *
 * @package panmotors
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run with: wp eval-file dev/seed.php\n" );
}

// Content is owned by the administrator, never by a user that may be deleted later: deleting a
// user without --reassign trashes their pages (docs/migration-blocks.md §0).
if ( 1 !== get_current_user_id() ) {
	WP_CLI::error( 'Run the seed as the administrator: wp eval-file dev/seed.php --user=1' );
}

require_once __DIR__ . '/lib.php';

$pm_reset = panmotors_seed_reset();
// --reset-demo overwrites content: only on a local or development copy. WP_ENVIRONMENT_TYPE must
// say so (a missing value counts as production in WordPress).
if ( $pm_reset && ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
	WP_CLI::error( '--reset-demo overwrites content and runs only when WP_ENVIRONMENT_TYPE is local or development (here: ' . wp_get_environment_type() . ').' );
}

// The seed imports acf-json into the database; only from committed files.
exec( 'git -C ' . escapeshellarg( get_template_directory() ) . ' status --porcelain -- acf-json', $pm_git ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
if ( $pm_git ) {
	WP_CLI::error( 'acf-json has uncommitted changes. Commit them, then run the seed.' );
}

if ( ! function_exists( 'panmotors_has_acf_pro' ) || ! panmotors_has_acf_pro() ) {
	WP_CLI::error( 'ACF PRO is not active. Install and activate it, then run the seed again.' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

const PANMOTORS_SEED_SRC = __DIR__ . '/../_design/uploads/';

if ( ! is_dir( PANMOTORS_SEED_SRC ) ) {
	WP_CLI::error( 'Missing _design/uploads. Copy the Claude Design export into _design/ first.' );
}

panmotors_dev_backup( $pm_reset ? 'seed-reset' : 'seed' );
WP_CLI::log( $pm_reset ? 'Mode: --reset-demo, demo content is overwritten.' : 'Mode: create what is missing, keep everything else.' );

/*
 * ------------------------------------------------------------------
 * 1. Field groups: update the database copies from acf-json, so wp-admin matches the repo.
 * ------------------------------------------------------------------
 */
// Update in place: with the existing ID, ACF also removes fields that are no longer in the JSON.
// Never acf_delete_field_group() here, it deletes the acf-json file too.
// ACF would also write each imported group back to acf-json with a new timestamp: not during the seed.
acf_update_setting( 'json', false );
foreach ( glob( get_template_directory() . '/acf-json/group_*.json' ) as $pm_json ) {
	$pm_group = json_decode( file_get_contents( $pm_json ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! $pm_group ) {
		continue;
	}
	$pm_existing    = acf_get_field_group_post( $pm_group['key'] );
	$pm_group['ID'] = $pm_existing ? $pm_existing->ID : 0;
	acf_import_field_group( $pm_group );
	WP_CLI::log( "Field group: {$pm_group['title']}" );
}
acf_update_setting( 'json', true );

// Remove database groups whose JSON is gone (D11: the per-page and Home groups).
// Their JSON file no longer exists, so acf_delete_field_group() has nothing else to delete.
foreach ( get_posts( array( 'post_type' => 'acf-field-group', 'post_status' => 'any', 'posts_per_page' => -1 ) ) as $pm_old ) {
	if ( str_starts_with( $pm_old->post_name, 'group_pm_' ) && ! file_exists( get_template_directory() . "/acf-json/{$pm_old->post_name}.json" ) ) {
		acf_delete_field_group( $pm_old->ID );
		WP_CLI::log( "Removed field group: {$pm_old->post_title}" );
	}
}

/*
 * ------------------------------------------------------------------
 * 2. Media.
 * ------------------------------------------------------------------
 */

/**
 * Import one file from _design/uploads, or return the existing attachment. Title, caption and alt
 * are written when the file is imported, and again only with --reset-demo.
 *
 * @param string $original Original file name in _design/uploads.
 * @param string $filename New, descriptive file name.
 * @param string $title    Attachment title.
 * @param string $alt      Alt text (images only).
 * @param string $caption  Attachment caption.
 * @return int Attachment ID, or 0 when the client deleted it.
 */
function panmotors_seed_media( $original, $filename, $title, $alt = '', $caption = '' ) {
	$id  = panmotors_dev_media( $original );
	$key = 'media:' . $original;

	if ( $id ) {
		panmotors_seed_created( $key, true );
		if ( ! panmotors_seed_reset() ) {
			return $id;
		}
	} elseif ( panmotors_seed_created( $key ) && ! panmotors_seed_reset() ) {
		return 0; // Deleted by the client: not imported again.
	} else {
		$tmp = wp_tempnam( $filename );
		copy( PANMOTORS_SEED_SRC . $original, $tmp );

		$id = media_handle_sideload(
			array(
				'name'     => $filename,
				'tmp_name' => $tmp,
			),
			0,
			$title
		);

		if ( is_wp_error( $id ) ) {
			WP_CLI::error( "{$original}: " . $id->get_error_message() );
		}

		update_post_meta( $id, '_pm_seed_source', $original );
		update_post_meta( $id, '_pm_demo', 1 );
		panmotors_seed_created( $key, true );
		WP_CLI::log( "Imported {$original} as {$filename}" );
	}

	wp_update_post(
		array(
			'ID'           => $id,
			'post_title'   => $title,
			'post_excerpt' => $caption,
		)
	);

	if ( $alt ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	}

	return $id;
}

$pm_media = array(
	'logo'           => panmotors_seed_media( '9JanArtboard-1-copy-20@1920x-Photoroom-c10d54bf.png', 'pan-motors-logo.png', 'Pan Motors logo', 'Pan Motors' ),

	// Real photographs of the showroom.
	'sr_night'       => panmotors_seed_media( 'DSC04357-copy-scaled.jpg', 'pan-motors-showroom-avenue-65-mesoyi-paphos-night.jpg', 'Pan Motors showroom at night', 'Pan Motors showroom on Avenue 65 in Mesoyi, Paphos, lit up at night', 'Avenue 65, by night' ),
	'sr_bay'         => panmotors_seed_media( 'IMG_6844-scaled.jpg', 'porsche-911-pan-motors-showroom-paphos.jpg', 'Porsche 911 in the Pan Motors showroom', 'Rear of a black Porsche 911 on the Pan Motors showroom floor, Paphos', 'Bay one, Porsche 911' ),
	'sr_floor'       => panmotors_seed_media( 'DSC08440-copy-Large.jpg', 'pan-motors-showroom-floor-paphos.jpg', 'Pan Motors showroom floor', 'Black Porsche 911 facing the entrance inside the Pan Motors showroom, Paphos', 'The floor, Paphos' ),
	'sr_forecourt'   => panmotors_seed_media( 'DSC08476-copy-Large.jpg', 'pan-motors-forecourt-mesoyi-paphos.jpg', 'Pan Motors forecourt', 'White Mercedes-Benz cars on the Pan Motors forecourt in Mesoyi, Paphos', 'Forecourt, Mesoyi' ),

	// Placeholder car photographs. The client replaces these.
	'porsche_studio' => panmotors_seed_media( 'black-porsche-911-luxury-sports-car-with-glossy-reflections-studio-lighting-generative-ai.jpg', 'black-porsche-911-studio.jpg', 'Black Porsche 911, studio', 'Black Porsche 911 in low studio light' ),
	'ferrari_rear'   => panmotors_seed_media( 'black-sports-car-with-number-37-back.jpg', 'black-ferrari-458-rear-studio.jpg', 'Black Ferrari 458, rear', 'Black Ferrari 458 seen from the rear three-quarter in studio light' ),
	'mclaren'        => panmotors_seed_media( 'close-up-mclaren-720s-indoor-showroom-with-checkered-floor.jpg', 'black-mclaren-720s-bronze-wheels.jpg', 'Black McLaren 720S', 'Black McLaren 720S with bronze wheels on a checkered floor' ),
	'red_night'      => panmotors_seed_media( 'red-sports-car-is-driving-empty-road-night-there-are-tall-buildings-background.jpg', 'red-sports-car-night-road.jpg', 'Red sports car at night', 'Red sports car on an empty road at night with city lights behind' ),
	'black_studio'   => panmotors_seed_media( 'sleek-black-sports-car-dramatic-lighting.jpg', 'black-sports-car-studio-light.jpg', 'Black sports car, studio light', 'Black sports car under a single studio light' ),
	'grey_sunset'    => panmotors_seed_media( 'sunset-supercar.jpg', 'dark-grey-supercar-sunset.jpg', 'Dark grey supercar at sunset', 'Dark grey supercar on a wet road at sunset' ),

	// Video.
	'v_hero'         => panmotors_seed_media( '0_Car_Automotive_1280x720.mp4', 'pan-motors-hero-night-drive.mp4', 'Hero video, night drive' ),
	'v_cinematic'    => panmotors_seed_media( '0_Automotive_Cinematic_720x1280.mp4', 'pan-motors-live-cinematic.mp4', 'Live, cinematic' ),
	'v_coast'        => panmotors_seed_media( '0_Car_Road_720x1280.mp4', 'pan-motors-live-coast-road.mp4', 'Live, coast road' ),
	'v_silent'       => panmotors_seed_media( '0_Electric_Car_Luxury_Car_720x1280.mp4', 'pan-motors-live-silent-run.mp4', 'Live, silent run' ),
);

/*
 * ------------------------------------------------------------------
 * 3. Options page: business facts.
 * ------------------------------------------------------------------
 */
$pm_options = array(
	'legal_name'             => 'Pan Motors Ltd',
	'trading_name'           => 'Pan Motors',
	'description'            => 'Pan Motors is a family-run luxury and performance car boutique on Avenue 65 in Mesoyi, Paphos, Cyprus.',
	'founded_year'           => '',
	'street_address'         => 'Avenue 65',
	'locality'               => 'Mesoyi',
	'city'                   => 'Paphos',
	'postcode'               => '8060',
	'country'                => 'CY',
	'country_name'           => 'Cyprus',
	'latitude'               => '', // Client supplies from the Google Business Profile pin.
	'longitude'              => '',
	'map_url'                => 'https://www.google.com/maps/search/?api=1&query=Pan+Motors+Avenue+65+Mesoyi+Paphos',
	'phones'                 => array(
		array(
			'number' => '+357 99 575 001',
			'label'  => '',
		),
		array(
			'number' => '+357 99 339 233',
			'label'  => '',
		),
	),
	'email'                  => 'info@panmotors.com',
	'hours'                  => array(
		array(
			'day'    => 'Monday — Friday',
			'time'   => '08:00 — 13:00',
			'note'   => 'Morning',
			'days'   => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
			'opens'  => '08:00:00',
			'closes' => '13:00:00',
		),
		array(
			'day'    => 'Monday — Friday',
			'time'   => '14:30 — 18:00',
			'note'   => 'Afternoon',
			'days'   => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
			'opens'  => '14:30:00',
			'closes' => '18:00:00',
		),
		array(
			'day'    => 'Saturday',
			'time'   => '08:00 — 13:00',
			'note'   => 'Morning only',
			'days'   => array( 'Saturday' ),
			'opens'  => '08:00:00',
			'closes' => '13:00:00',
		),
	),
	'marques'                => array_map(
		fn( $name ) => array( 'name' => $name ),
		array( 'Porsche', 'Maserati', 'Ferrari', 'Lamborghini', 'Aston Martin', 'Bentley' )
	),
	'instagram_url'          => 'https://www.instagram.com/panmotors/',
	'facebook_url'           => '',
	'google_business_url'    => '',
	'other_profiles'         => array(),
	'enquire_form_shortcode' => '',
	'footer_tagline'         => 'Pan Motors — Paphos',
	'footer_copyright'       => '© {year} Pan Motors',
	'map_embed_query'        => 'Pan Motors Mesoyi Paphos Cyprus',
	// Our Values (D12): one set for Home and About, from _design/v2/about.html.
	'values'                 => array(
		array(
			'index' => '01',
			'title' => 'Chosen',
			'body'  => 'We only show cars we would drive ourselves. Each one is selected, not stocked.',
		),
		array(
			'index' => '02',
			'title' => 'Transparent',
			'body'  => 'Full history, clear paperwork and straight answers on every car.',
		),
		array(
			'index' => '03',
			'title' => 'Looked After',
			'body'  => 'Service and care continue long after the car leaves the showroom.',
		),
		array(
			'index' => '04',
			'title' => 'Personal',
			'body'  => 'A family business. You deal with the same people from first visit to handover.',
		),
	),
	'contact_button_label'   => 'Contact',
	'marquee_separator'      => '—',
	'notfound_code'          => '404',
	'notfound_eyebrow'       => 'Page not found',
	'notfound_title'         => 'Off the Map',
	'notfound_text'          => 'The page you were looking for has moved or no longer exists. The cars are still where we left them.',
	'notfound_home_label'    => 'Back to home',
	'notfound_contact_label' => 'Contact us',
);

// Only options that were never saved: a field the client emptied stays empty.
$pm_filled = 0;
foreach ( $pm_options as $pm_name => $pm_value ) {
	if ( ! $pm_reset && null !== get_option( 'options_' . $pm_name, null ) ) {
		continue;
	}
	update_field( 'field_pm_' . $pm_name, $pm_value, 'option' );
	++$pm_filled;
}
WP_CLI::log( "Options: {$pm_filled} filled." );

/*
 * ------------------------------------------------------------------
 * 4. Blocks (D11): helpers to write pages as real block markup.
 * ------------------------------------------------------------------
 */

// Block markup helpers (panmotors_seed_block() and friends) are in dev/lib.php.

/**
 * A published page built from blocks. Created when missing (and not deleted by the client before).
 * An existing page is left as it is; --reset-demo rewrites a demo page's content.
 *
 * @param string $slug    Page slug.
 * @param string $title   Page title.
 * @param string $content Block markup.
 * @return int Page ID, or 0 when the client deleted it.
 */
function panmotors_seed_block_page( $slug, $title, $content ) {
	return panmotors_seed_page( $slug, $title, $content );
}

/**
 * A published page. Created when missing and not deleted by the client before; an existing page
 * is left as it is. --reset-demo rewrites the title and content of a demo page.
 *
 * @param string $slug     Page slug.
 * @param string $title    Page title (the H1 when the page has no page header).
 * @param string $content  Post content (block markup or plain HTML).
 * @param int    $existing Existing page ID to use, if any (WordPress's own privacy page).
 * @return int Page ID, or 0 when the client deleted it.
 */
function panmotors_seed_page( $slug, $title, $content = '', $existing = 0 ) {
	$page = $existing ? get_post( $existing ) : get_page_by_path( $slug );
	$page = ( $page && 'trash' !== $page->post_status ) ? $page : null;
	$key  = 'page:' . $slug;

	if ( $page ) {
		panmotors_seed_created( $key, true );
		if ( panmotors_seed_reset() && get_post_meta( $page->ID, '_pm_demo', true ) ) {
			wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_title'   => $title,
					'post_content' => wp_slash( $content ), // Keep JSON escapes (\n) in block attributes.
				)
			);
			WP_CLI::log( "Page /{$slug}/: reset to the demo content." );
		}
		return (int) $page->ID;
	}

	if ( panmotors_seed_created( $key ) && ! panmotors_seed_reset() ) {
		WP_CLI::log( "Page /{$slug}/: deleted by the client, not created again." );
		return 0;
	}

	$id = (int) wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => wp_slash( $content ),
		)
	);
	update_post_meta( $id, '_wp_page_template', 'default' );
	update_post_meta( $id, '_pm_demo', 1 );
	panmotors_seed_created( $key, true );
	WP_CLI::log( "Page /{$slug}/: created." );
	return $id;
}

/*
 * ------------------------------------------------------------------
 * 5. Cars (pm_car). Matched on _pm_seed_key: created when missing, never duplicated; an
 *    existing car is only rewritten with --reset-demo.
 *    Dates set the Latest Cars order (newest first); Featured cars in menu_order.
 * ------------------------------------------------------------------
 */
// The first design's cars (Home: Featured Cars row, Latest Cars). Only the McLaren 720S is also in
// the v2 Featured Cars page; the other five are not on that page ("on_page" off).
$pm_car = static fn( $marque, $model, $extra = array() ) => array_merge(
	array(
		'marque'   => $marque,
		'model'    => $model,
		'ref'      => '',
		'spec'     => '',
		'note'     => '',
		'caption'  => '',
		'featured' => 0,
		'order'    => 0,
		'image'    => '',
		'on_page'  => 1,
	),
	$extra
);
$pm_old  = array( 'on_page' => 0 );
$pm_cars = array(
	'mclaren'        => $pm_car( 'McLaren', '720S', array( 'caption' => 'Bay four, morning light', 'order' => 3, 'image' => 'mclaren', 'year' => '2021', 'power' => '720 hp', 'acceleration' => '0–100 in 2.9 s', 'mileage' => '11,200 km', 'engine' => '4.0 V8, twin turbo', 'gearbox' => 'SSG, 7 speed', 'colour' => 'Silica white', 'note' => 'Performance spec, carbon exterior pack.' ) ),
	'red_night'      => $pm_car( 'Performance', 'Red Coupé', $pm_old + array( 'caption' => 'Night run, empty ring road', 'image' => 'red_night' ) ),
	'black_studio'   => $pm_car( 'Track', 'Winged Coupé', $pm_old + array( 'ref' => 'No. 17', 'spec' => 'Naturally aspirated', 'note' => 'Carbon aero', 'caption' => 'Single lamp, no reflectors', 'featured' => 1, 'order' => 4, 'image' => 'black_studio' ) ),
	'grey_sunset'    => $pm_car( 'Grand Touring', 'Mid-Engine Coupé', $pm_old + array( 'ref' => 'No. 09', 'spec' => 'Twin-turbo V8', 'note' => 'Last light', 'caption' => 'After the rain, last light', 'featured' => 1, 'order' => 2, 'image' => 'grey_sunset' ) ),
	'ferrari_rear'   => $pm_car( 'Ferrari', '458 Italia', $pm_old + array( 'ref' => 'No. 12', 'spec' => 'V8', 'note' => 'Single owner', 'caption' => 'Rear three-quarter, no. 458', 'featured' => 1, 'order' => 3, 'image' => 'ferrari_rear' ) ),
	'porsche_studio' => $pm_car( 'Porsche', '911 Carrera', $pm_old + array( 'ref' => 'No. 04', 'spec' => 'Flat six', 'note' => 'Kept in slate grey', 'caption' => 'Glass black, held on the line', 'featured' => 1, 'order' => 1, 'image' => 'porsche_studio' ) ),
);
// The v2 Featured Cars page (_design/v2/cars.html): twelve cars, the McLaren above is No. 03. Not
// Featured, so the homepage row keeps its four; dated before the cars above, so Latest Cars on Home
// keeps its six. Two have no photo yet.
$pm_v2 = array(
	'v2_911_turbo_s'     => array( 'Porsche', '911 Turbo S', 1, '2023', '650 hp', '0–100 in 2.7 s', '4,800 km', '3.8 flat six, twin turbo', 'PDK, 8 speed', 'Jet black', 'porsche_studio', 'Full Porsche service history, ceramic brakes, sport chrono.' ),
	'v2_296_gtb'         => array( 'Ferrari', '296 GTB', 2, '2022', '830 hp', '0–100 in 2.9 s', '9,400 km', '3.0 V6 hybrid', 'DCT, 8 speed', 'Rosso Corsa', 'ferrari_rear', 'Single owner from new, Assetto Fiorano pack.' ),
	'v2_mc20_cielo'      => array( 'Maserati', 'MC20 Cielo', 4, '2024', '630 hp', '0–100 in 3.0 s', '1,200 km', '3.0 V6 Nettuno', 'DCT, 8 speed', 'Bianco Audace', 'grey_sunset', 'Retractable glass roof, delivered new in Modena.' ),
	'v2_718_spyder_rs'   => array( 'Porsche', '718 Spyder RS', 5, '2024', '500 hp', '0–100 in 3.4 s', '600 km', '4.0 flat six', 'PDK, 7 speed', 'Graphite grey', 'black_studio', 'Weissach package, lift system.' ),
	'v2_huracan_tecnica' => array( 'Lamborghini', 'Huracán Tecnica', 6, '2023', '640 hp', '0–100 in 3.2 s', '3,100 km', '5.2 V10', 'DCT, 7 speed', 'Rosso Mars', 'red_night', 'Rear wheel drive, one Cyprus owner.' ),
	'v2_911_carrera_gts' => array( 'Porsche', '911 Carrera GTS', 7, '2023', '480 hp', '0–100 in 3.4 s', '7,600 km', '3.0 flat six, twin turbo', 'PDK, 8 speed', 'Guards red', 'sr_bay', 'Rear axle steering, sport exhaust.' ),
	'v2_roma'            => array( 'Ferrari', 'Roma', 8, '2022', '620 hp', '0–100 in 3.4 s', '8,900 km', '3.9 V8, twin turbo', 'DCT, 8 speed', 'Grigio Titanio', 'sr_floor', 'Passenger display, adaptive headlights.' ),
	'v2_granturismo'     => array( 'Maserati', 'GranTurismo Trofeo', 9, '2024', '550 hp', '0–100 in 3.5 s', '2,400 km', '3.0 V6 Nettuno', 'Auto, 8 speed', 'Blu Nobile', 'sr_night', 'Four seats, Sonus Faber audio.' ),
	'v2_continental_gt'  => array( 'Bentley', 'Continental GT Speed', 10, '2022', '659 hp', '0–100 in 3.6 s', '12,800 km', '6.0 W12, twin turbo', 'DCT, 8 speed', 'Onyx', 'sr_forecourt', 'Mulliner driving spec, Naim audio.' ),
	'v2_db12'            => array( 'Aston Martin', 'DB12', 11, '2024', '680 hp', '0–100 in 3.6 s', '1,900 km', '4.0 V8, twin turbo', 'Auto, 8 speed', 'Magnetic silver', '', 'Launch edition, Bowers & Wilkins audio.' ),
	'v2_urus_performante' => array( 'Lamborghini', 'Urus Performante', 12, '2023', '666 hp', '0–100 in 3.3 s', '6,300 km', '4.0 V8, twin turbo', 'Auto, 8 speed', 'Nero Noctis', '', 'Akrapovič exhaust, carbon roof.' ),
);
foreach ( $pm_v2 as $pm_key => list( $pm_m, $pm_mo, $pm_o, $pm_y, $pm_pw, $pm_ac, $pm_km, $pm_en, $pm_gb, $pm_co, $pm_img, $pm_nt ) ) {
	$pm_cars[ $pm_key ] = $pm_car( $pm_m, $pm_mo, array( 'order' => $pm_o, 'year' => $pm_y, 'power' => $pm_pw, 'acceleration' => $pm_ac, 'mileage' => $pm_km, 'engine' => $pm_en, 'gearbox' => $pm_gb, 'colour' => $pm_co, 'image' => $pm_img, 'note' => $pm_nt ) );
}

$pm_day = 0;
foreach ( $pm_cars as $pm_key => $pm_c ) {
	// First design: 20 Sep and the days before. v2 cars: from 1 Sep back.
	$pm_date  = str_starts_with( $pm_key, 'v2_' )
		? strtotime( '2026-09-01 10:00:00' ) - ( $pm_c['order'] * DAY_IN_SECONDS )
		: strtotime( '2026-09-20 10:00:00' ) - $pm_day++ * DAY_IN_SECONDS;
	$pm_found = get_posts(
		array(
			'post_type'      => 'pm_car',
			'post_status'    => 'any',
			'meta_key'       => '_pm_seed_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $pm_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	$pm_car_key = 'car:' . $pm_key;
	if ( $pm_found ) {
		panmotors_seed_created( $pm_car_key, true );
		if ( ! $pm_reset ) {
			continue;
		}
	} elseif ( panmotors_seed_created( $pm_car_key ) && ! $pm_reset ) {
		continue; // Deleted by the client.
	}
	$pm_post = array(
		'post_type'   => 'pm_car',
		'post_status' => 'publish',
		'post_title'  => "{$pm_c['marque']} {$pm_c['model']}",
		'menu_order'  => $pm_c['order'],
		'post_date'   => gmdate( 'Y-m-d H:i:s', $pm_date ),
	);
	if ( $pm_found ) {
		$pm_post['ID'] = $pm_found[0];
	}
	$pm_car_id = (int) ( isset( $pm_post['ID'] ) ? wp_update_post( $pm_post ) : wp_insert_post( $pm_post ) );
	panmotors_seed_created( $pm_car_key, true );
	update_post_meta( $pm_car_id, '_pm_seed_key', $pm_key );
	update_post_meta( $pm_car_id, '_pm_demo', 1 );
	$pm_fields = array(
		'car_image'     => $pm_c['image'] ? $pm_media[ $pm_c['image'] ] : '', // Empty: the page shows the plain card.
		'car_marque'    => $pm_c['marque'],
		'car_model'     => $pm_c['model'],
		'car_ref'       => $pm_c['ref'],
		'car_spec'      => $pm_c['spec'],
		'car_note'      => $pm_c['note'],
		'car_link'      => '',
		'slide_caption' => $pm_c['caption'],
		'slide_place'   => 'Paphos',
		'car_featured'  => $pm_c['featured'],
		'car_on_page'   => $pm_c['on_page'],
	);
	foreach ( array( 'year' => 'car_year', 'power' => 'car_power', 'acceleration' => 'car_sprint', 'mileage' => 'car_mileage', 'engine' => 'car_engine', 'gearbox' => 'car_gearbox', 'colour' => 'car_colour' ) as $pm_name => $pm_field ) {
		$pm_fields[ $pm_field ] = $pm_c[ $pm_name ] ?? '';
	}
	foreach ( $pm_fields as $pm_field => $pm_value ) {
		update_field( 'field_pm_' . $pm_field, $pm_value, $pm_car_id );
	}
	WP_CLI::log( "Car {$pm_c['marque']} {$pm_c['model']}: " . ( $pm_found ? 'reset.' : 'created.' ) );
}
WP_CLI::log( 'Cars: ' . count( $pm_cars ) . ' in the seed, missing ones created.' );

/*
 * ------------------------------------------------------------------
 * 6. Pages as blocks (D11). Section pages keep their slugs; their old per-page fields become
 *    blocks on the page itself. Inner pages wait for their design (D10).
 * ------------------------------------------------------------------
 */
$pm_cta = panmotors_seed_block(
	'pm/cta-band',
	array(
		'cta_title' => 'Come and see',
		'cta_text'  => 'Call, write, or walk in during showroom hours. Someone from the family will answer.',
		'cta_label' => 'Contact us',
		'cta_link'  => get_page_by_path( 'contact' ) ? get_page_by_path( 'contact' )->ID : 0,
	)
);

$pm_about_block = panmotors_seed_block(
	'pm/about',
	array(
		'about_image'   => $pm_media['mclaren'],
		'about_eyebrow' => 'Mesoyi, Paphos',
		'about_title'   => 'About Pan Motors',
		'about_stats'   => array(
			array(
				'value' => 'Sales',
				'label' => 'Luxury and performance',
			),
			array(
				'value' => 'Service',
				'label' => 'Aftercare in house',
			),
			array(
				'value' => 'Boutique',
				'label' => 'Parts and accessories',
			),
		),
		'about_fade'    => 1,
	)
);

$pm_showroom_block = panmotors_seed_block(
	'pm/showroom',
	array(
		'showroom_eyebrow' => 'Avenue 65, Mesoyi',
		'showroom_title'   => 'The Showroom',
		'showroom_intro'   => 'Paphos, open six days a week. Sales, service and the boutique under one roof.',
		'showroom_photos'  => array( $pm_media['sr_night'], $pm_media['sr_bay'], $pm_media['sr_floor'], $pm_media['sr_forecourt'] ),
		'showroom_hours'   => 1,
	)
);

$pm_enquire_block = panmotors_seed_block(
	'pm/enquire',
	array(
		'enquire_title'      => 'Come And See',
		'enquire_intro'      => 'Call, write, or walk in during showroom hours. Someone from the family will answer.',
		'form_label_name'    => 'Name',
		'form_hint_name'     => 'Full name',
		'form_label_email'   => 'Email',
		'form_hint_email'    => 'you@domain.com',
		'form_label_phone'   => 'Phone',
		'form_hint_phone'    => '+357',
		'form_label_message' => 'Message',
		'form_hint_message'  => 'Tell us which car you are interested in.',
		'form_button'        => 'Send message',
	)
);

$pm_hero = static fn( $eyebrow, $intro, $image ) => panmotors_seed_block(
	'pm/page-hero',
	array(
		'page_eyebrow'    => $eyebrow,
		'page_intro'      => $intro,
		'page_hero_image' => $image,
	)
);

// Contact page questions (DRAFT answers for the client to confirm).
$pm_faq_block = panmotors_seed_block(
	'pm/faq',
	array(
		'faq_title' => 'Questions',
		'faqs'      => array(
			array(
				'question' => 'Where is Pan Motors?',
				'answer'   => 'DRAFT: Pan Motors is on Avenue 65 in Mesoyi, Paphos 8060, Cyprus. [Client to add a landmark and parking details.]',
			),
			array(
				'question' => 'What are your opening hours?',
				'answer'   => 'DRAFT: Monday to Friday 08:00 to 13:00 and 14:30 to 18:00, Saturday 08:00 to 13:00. Closed on Sunday.',
			),
			array(
				'question' => 'Do I need an appointment to visit the showroom?',
				'answer'   => 'DRAFT: You are welcome to walk in during opening hours. For a private viewing, call or write ahead and we will set a time. [Client to confirm.]',
			),
			array(
				'question' => 'Which marques do you work with?',
				'answer'   => 'DRAFT: Porsche, Maserati, Ferrari, Lamborghini, Aston Martin and Bentley, among others. [Client to confirm the list.]',
			),
			array(
				'question' => 'Do you look after cars after purchase?',
				'answer'   => 'DRAFT: Yes. Service and aftercare are handled in house in Paphos, by the same people who prepared the car. [Client to confirm scope.]',
			),
			array(
				'question' => 'What is in the boutique?',
				'answer'   => 'DRAFT: Parts and accessories for the marques we work with. [Client to describe the range.]',
			),
			array(
				'question' => 'Do you deliver outside Paphos?',
				'answer'   => 'DRAFT: [Client to confirm whether cars are delivered across Cyprus, and on what terms.]',
			),
		),
	)
);

$pm_pages = array(
	'latest'   => panmotors_seed_block_page(
		'latest-cars',
		'Latest Cars',
		implode(
			"\n\n",
			array(
				$pm_hero( 'Latest arrivals', 'Recent arrivals at the Paphos showroom, photographed as they came in.', $pm_media['mclaren'] ),
				panmotors_seed_paragraphs( '<p>New cars reach Pan Motors throughout the year. This page shows the most recent arrivals in Paphos, before they join the featured collection or find their next owner.</p>' ),
				panmotors_seed_block(
					'pm/latest-cars',
					array(
						'latest_eyebrow' => 'Latest arrivals',
						'latest_title'   => 'Latest Cars',
						'latest_limit'   => 12,
					)
				),
				$pm_cta,
			)
		)
	),
	'contact'  => panmotors_seed_block_page( 'contact', 'Contact', panmotors_demo_contact_content( $pm_faq_block ) ),

	// After Contact, which its car sheets and CTA band link to (D12, _design/v2/cars.html).
	'featured' => panmotors_seed_block_page( 'featured-cars', 'Featured Cars', panmotors_demo_featured_content() ),
	// After Contact and Featured Cars, which its call to action links to (D12, _design/v2/showroom.html).
	'showroom' => panmotors_seed_block_page( 'showroom', 'The Showroom', panmotors_demo_showroom_content() ),
	// After Showroom and Contact, which its call to action links to (D12, _design/v2/about.html).
	'about'    => panmotors_seed_block_page( 'about', 'About Pan Motors', panmotors_demo_about_content() ),
);

// The Contact button (header, mobile menu, 404) links here, unless already set.
if ( $pm_reset || ! get_option( 'options_page_contact' ) ) {
	update_field( 'field_pm_page_contact', $pm_pages['contact'], 'option' );
}

// Legal pages on page.php, created only when missing. WordPress's own privacy page is reused if it
// exists (its own draft text is then kept).
$pm_legal_draft = '<p>DRAFT: [Client to supply the %s. The text below this line is a placeholder.]</p><h2>Who we are</h2><p>Pan Motors Ltd, Avenue 65, Mesoyi, Paphos 8060, Cyprus.</p>';
$pm_privacy_id  = panmotors_seed_page( 'privacy-policy', 'Privacy Policy', sprintf( $pm_legal_draft, 'privacy policy' ), (int) get_option( 'wp_page_for_privacy_policy' ) );
$pm_cookie_id   = panmotors_seed_page( 'cookie-policy', 'Cookie Policy', sprintf( $pm_legal_draft, 'cookie policy' ) );
if ( $pm_privacy_id && ! get_option( 'wp_page_for_privacy_policy' ) ) {
	update_option( 'wp_page_for_privacy_policy', $pm_privacy_id );
}

/*
 * ------------------------------------------------------------------
 * 7. Home: the full design as blocks. The hero cannot be moved or removed.
 * ------------------------------------------------------------------
 */
// Home's Our Values: dark cards linking to About (created above).
$pm_values_dark  = panmotors_seed_block(
	'pm/values',
	array(
		'values_style' => 'dark',
		'values_title' => 'Our Values',
		'values_link'  => $pm_pages['about'],
	)
);
$pm_home_content = implode(
	"\n\n",
	array(
		panmotors_seed_block(
			'pm/hero',
			array(
				'hero_eyebrow'   => 'Pan Motors, luxury car boutique in Paphos, Cyprus',
				'hero_title'     => "Luxury\nin Motion",
				'hero_video'     => $pm_media['v_hero'],
				'hero_poster'    => $pm_media['red_night'],
				'hero_cta_label' => 'View the cars',
				'hero_cta_link'  => $pm_pages['featured'],
			),
			array(
				'lock' => array(
					'move'   => true,
					'remove' => true,
				),
			)
		),
		panmotors_seed_block( 'pm/marquee' ),
		panmotors_seed_block(
			'pm/featured-cars',
			array(
				'featured_title'  => 'Featured Cars',
				'featured_intro'  => 'A rotating selection of luxury and performance cars, prepared and presented in our Paphos showroom.',
				'featured_source' => 'featured',
				'featured_limit'  => 4,
				'featured_more_label' => 'All featured cars',
				'featured_more_link'  => $pm_pages['featured'],
			)
		),
		$pm_values_dark,
		$pm_about_block,
		panmotors_seed_block(
			'pm/latest-cars',
			array(
				'latest_eyebrow' => 'Latest arrivals',
				'latest_title'   => 'Latest Cars',
				'latest_limit'   => 6,
			)
		),
		panmotors_seed_block(
			'pm/live',
			array(
				'live_eyebrow'   => 'Social',
				'live_title'     => 'Pan Motors Live',
				'live_cta_label' => 'Follow the floor',
				'live_posts'     => array(
					array(
						'type'     => 'video',
						'video'    => $pm_media['v_cinematic'],
						'image'    => '',
						'url'      => 'https://www.instagram.com/reel/DdHtvoxqTge/',
						'caption'  => 'Cinematic',
						'likes'    => '24K',
						'comments' => '188',
					),
					array(
						'type'     => 'video',
						'video'    => $pm_media['v_coast'],
						'image'    => '',
						'url'      => 'https://www.instagram.com/reel/DcrFwhYifCK/',
						'caption'  => 'Coast road',
						'likes'    => '138K',
						'comments' => '407',
					),
					array(
						'type'     => 'photo',
						'video'    => '',
						'image'    => $pm_media['grey_sunset'],
						'url'      => 'https://www.instagram.com/reel/DZar26hC_wg/',
						'caption'  => 'Last light',
						'likes'    => '123K',
						'comments' => '432',
					),
					array(
						'type'     => 'video',
						'video'    => $pm_media['v_silent'],
						'image'    => '',
						'url'      => 'https://www.instagram.com/reel/DQMRsfkjFY2/',
						'caption'  => 'Silent run',
						'likes'    => '83K',
						'comments' => '774',
					),
					array(
						'type'     => 'video',
						'video'    => $pm_media['v_hero'],
						'image'    => $pm_media['red_night'],
						'url'      => 'https://www.instagram.com/reel/C2mB7yrCFWs/',
						'caption'  => 'Night pass',
						'likes'    => '151K',
						'comments' => '734',
					),
					array(
						'type'     => 'photo',
						'video'    => '',
						'image'    => $pm_media['porsche_studio'],
						'url'      => 'https://www.instagram.com/panmotors/',
						'caption'  => 'Studio',
						'likes'    => '69K',
						'comments' => '496',
					),
				),
			)
		),
		$pm_showroom_block,
		$pm_enquire_block,
	)
);

// Home: created when missing (found by the front page setting, else by title). An existing Home is
// never rewritten, except a demo Home with --reset-demo.
$pm_front_id = (int) get_option( 'page_on_front' );
if ( ! $pm_front_id || ! get_post( $pm_front_id ) || 'trash' === get_post_status( $pm_front_id ) ) {
	$pm_home     = get_posts(
		array(
			'post_type'      => 'page',
			'title'          => 'Home',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	$pm_front_id = $pm_home ? (int) $pm_home[0] : 0;
}
if ( $pm_front_id ) {
	panmotors_seed_created( 'page:home', true );
	if ( $pm_reset && get_post_meta( $pm_front_id, '_pm_demo', true ) ) {
		wp_update_post(
			array(
				'ID'           => $pm_front_id,
				'post_content' => wp_slash( $pm_home_content ),
			)
		);
		WP_CLI::log( "Home: page {$pm_front_id}, reset to the demo content." );
	}
} elseif ( ! panmotors_seed_created( 'page:home' ) || $pm_reset ) {
	$pm_front_id = (int) wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_title'   => 'Home',
			'post_status'  => 'publish',
			'post_content' => wp_slash( $pm_home_content ),
		)
	);
	update_post_meta( $pm_front_id, '_pm_demo', 1 );
	update_post_meta( $pm_front_id, '_wp_page_template', 'default' );
	panmotors_seed_created( 'page:home', true );
	WP_CLI::log( "Home: page {$pm_front_id}, created from blocks." );
}
if ( $pm_front_id && ( $pm_reset || 'page' !== get_option( 'show_on_front' ) || ! get_post( (int) get_option( 'page_on_front' ) ) ) ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $pm_front_id );
}

/*
 * ------------------------------------------------------------------
 * 8. Menus: page links only (D9).
 * ------------------------------------------------------------------
 */

/**
 * Create a menu of page links when it is missing and return its ID. An existing menu is left as it
 * is; --reset-demo rebuilds it.
 *
 * @param string $name  Menu name.
 * @param array  $links Label => page ID.
 * @return int
 */
function panmotors_seed_menu( $name, $links ) {
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu && ! panmotors_seed_reset() ) {
		return (int) $menu->term_id;
	}
	$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $name );

	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
		wp_delete_post( $item->ID, true );
	}

	$position = 0;
	foreach ( array_filter( $links ) as $label => $page_id ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $label,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page_id,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => ++$position,
			)
		);
	}

	WP_CLI::log( "Menu {$name}: {$position} links." );
	return $menu_id;
}

$pm_menus = array(
	'primary' => panmotors_seed_menu(
		'Primary',
		array(
			'Featured Cars'    => $pm_pages['featured'],
			'About Pan Motors' => $pm_pages['about'],
			'Latest Cars'      => $pm_pages['latest'],
			'Showroom'         => $pm_pages['showroom'],
		)
	),
	'footer'  => panmotors_seed_menu(
		'Footer',
		array(
			'Featured Cars'  => $pm_pages['featured'],
			'Showroom'       => $pm_pages['showroom'],
			'Contact'        => $pm_pages['contact'],
			'Privacy Policy' => $pm_privacy_id,
			'Cookie Policy'  => $pm_cookie_id,
		)
	),
);
// Only locations that have no menu yet.
$pm_locations = (array) get_theme_mod( 'nav_menu_locations', array() );
foreach ( $pm_menus as $pm_location => $pm_menu_id ) {
	if ( $pm_reset || empty( $pm_locations[ $pm_location ] ) ) {
		$pm_locations[ $pm_location ] = $pm_menu_id;
	}
}
set_theme_mod( 'nav_menu_locations', $pm_locations );

/*
 * ------------------------------------------------------------------
 * 9. Site settings.
 * ------------------------------------------------------------------
 */
if ( $pm_reset || in_array( get_option( 'blogname' ), array( '', 'My WordPress Blog', 'My WordPress Website' ), true ) ) {
	update_option( 'blogname', 'Pan Motors' );
}
if ( $pm_reset || ! get_option( 'permalink_structure' ) ) {
	update_option( 'permalink_structure', '/%postname%/' );
	flush_rewrite_rules( false );
}
if ( $pm_media['logo'] && ( $pm_reset || ! get_theme_mod( 'custom_logo' ) ) ) {
	set_theme_mod( 'custom_logo', $pm_media['logo'] );
}
update_option( 'panmotors_demo_content', gmdate( 'c' ), false );
wp_get_theme()->delete_pattern_cache(); // Pick up new files in patterns/.

WP_CLI::success( 'Demo content seeded.' );
