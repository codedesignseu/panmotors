<?php
/**
 * Demo content seed. Development only, excluded from deploys via .distignore.
 *
 * Run from Local's site shell, in the theme folder, as the administrator (user 1), after
 * committing acf-json/:
 *   wp eval-file dev/seed.php --user=1
 *
 * What it does (D11, docs/blocks.md):
 * - Imports _design/uploads into the media library with descriptive file names,
 *   titles, alt text and captions (theme-map 9.5).
 * - Updates the database copies of the field groups from acf-json, so they are editable in wp-admin,
 *   and removes the database copies of pm groups whose JSON is gone.
 * - Fills the Pan Motors options page with the business facts from the design.
 * - Creates the six demo cars (Cars post type) and the "Our Values" synced pattern.
 * - Writes the section pages and Home as block markup. A page that already has pm/* blocks is
 *   left alone (the client's layout); PM_SEED_REBUILD=1 rewrites them anyway.
 * - Removes the per-page field data of the old model from those pages.
 * - Creates the Primary and Footer menus with page links and assigns them.
 * - Sets a static front page, the site title and the custom logo.
 *
 * Copy marked DRAFT is for the client to confirm or replace.
 *
 * Safe to re-run: media is matched on the original file name, cars and the pattern on a seed key.
 * It never deletes pages, users, patterns or field groups it did not create.
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

/*
 * ------------------------------------------------------------------
 * 1. Field groups: update the database copies from acf-json, so wp-admin matches the repo.
 * ------------------------------------------------------------------
 */
// Update in place: with the existing ID, ACF also removes fields that are no longer in the JSON.
// Never acf_delete_field_group() here, it deletes the acf-json file too.
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
 * Import one file from _design/uploads, or return the existing attachment.
 *
 * @param string $original Original file name in _design/uploads.
 * @param string $filename New, descriptive file name.
 * @param string $title    Attachment title.
 * @param string $alt      Alt text (images only).
 * @param string $caption  Attachment caption.
 * @return int Attachment ID.
 */
function panmotors_seed_media( $original, $filename, $title, $alt = '', $caption = '' ) {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_pm_seed_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $original, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	if ( $existing ) {
		$id = (int) $existing[0];
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
	'contact_button_label'   => 'Contact',
	'marquee_separator'      => '—',
	'notfound_code'          => '404',
	'notfound_eyebrow'       => 'Page not found',
	'notfound_title'         => 'Off the Map',
	'notfound_text'          => 'The page you were looking for has moved or no longer exists. The cars are still where we left them.',
	'notfound_home_label'    => 'Back to home',
	'notfound_contact_label' => 'Contact us',
);

foreach ( $pm_options as $pm_name => $pm_value ) {
	update_field( 'field_pm_' . $pm_name, $pm_value, 'option' );
}
WP_CLI::log( 'Options page filled.' );

/*
 * ------------------------------------------------------------------
 * 4. Blocks (D11): helpers to write pages as real block markup.
 * ------------------------------------------------------------------
 */

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
 * A published page built from blocks. Created when missing. Its content is written only while it
 * has no pm/* blocks yet (or PM_SEED_REBUILD=1), so the client's own layout is kept. Never deletes.
 *
 * @param string $slug    Page slug.
 * @param string $title   Page title.
 * @param string $content Block markup.
 * @return int
 */
function panmotors_seed_block_page( $slug, $title, $content ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => wp_slash( $content ), // Keep JSON escapes (\n) in block attributes.
			)
		);
		update_post_meta( $id, '_pm_demo', 1 );
		WP_CLI::log( "Created page /{$slug}/" );
	} else {
		$id = $page->ID;
		if ( false === strpos( $page->post_content, '<!-- wp:pm/' ) || getenv( 'PM_SEED_REBUILD' ) ) {
			wp_update_post(
				array(
					'ID'           => $id,
					'post_content' => wp_slash( $content ),
				)
			);
			WP_CLI::log( "Page /{$slug}/: written as blocks." );
		}
	}
	update_post_meta( $id, '_wp_page_template', 'default' );
	return (int) $id;
}

/**
 * Delete post meta left by the field-group era (D9 page fields, Home fields). Only these keys.
 *
 * @param int $post_id Post ID.
 */
function panmotors_seed_forget_page_fields( $post_id ) {
	$old = array( 'page_eyebrow', 'page_intro', 'page_hero_image', 'page_body', 'featured_cars', 'about_image', 'about_story', 'about_stats', 'values', 'latest_cars', 'showroom_photos', 'getting_here', 'enquire_title', 'enquire_intro', 'faq_title', 'faqs', 'hero_', 'live_', 'home_', 'form_' );
	foreach ( array_keys( get_post_meta( $post_id ) ) as $key ) {
		$bare = ltrim( $key, '_' );
		foreach ( $old as $prefix ) {
			if ( $bare === $prefix || str_starts_with( $bare, rtrim( $prefix, '_' ) . '_' ) || ( str_ends_with( $prefix, '_' ) && str_starts_with( $bare, $prefix ) ) ) {
				delete_post_meta( $post_id, $key );
				break;
			}
		}
	}
}

/**
 * Create or update a published page and return its ID (legal pages, plain editor content).
 *
 * @param string $slug     Page slug.
 * @param string $title    Page title (the H1).
 * @param string $content  Post content.
 * @param int    $existing Existing page ID to reuse, if any.
 * @return int
 */
function panmotors_seed_page( $slug, $title, $content = '', $existing = 0 ) {
	$page = $existing ? get_post( $existing ) : get_page_by_path( $slug );
	$data = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
	);

	if ( $page ) {
		$data['ID'] = $page->ID;
		$id         = wp_update_post( $data );
	} else {
		$id = wp_insert_post( $data );
	}

	update_post_meta( $id, '_wp_page_template', 'default' );
	update_post_meta( $id, '_pm_demo', 1 );

	return (int) $id;
}

/*
 * ------------------------------------------------------------------
 * 5. Cars (pm_car). Matched on _pm_seed_key so re-runs update, never duplicate.
 *    Dates set the Latest Cars order (newest first); Featured cars in menu_order.
 * ------------------------------------------------------------------
 */
$pm_cars = array(
	'mclaren'        => array( 'McLaren', '720S', '', '', '', 'Bay four, morning light', 0, 0 ),
	'red_night'      => array( 'Performance', 'Red Coupé', '', '', '', 'Night run, empty ring road', 0, 0 ),
	'black_studio'   => array( 'Track', 'Winged Coupé', 'No. 17', 'Naturally aspirated', 'Carbon aero', 'Single lamp, no reflectors', 1, 4 ),
	'grey_sunset'    => array( 'Grand Touring', 'Mid-Engine Coupé', 'No. 09', 'Twin-turbo V8', 'Last light', 'After the rain, last light', 1, 2 ),
	'ferrari_rear'   => array( 'Ferrari', '458 Italia', 'No. 12', 'V8', 'Single owner', 'Rear three-quarter, no. 458', 1, 3 ),
	'porsche_studio' => array( 'Porsche', '911 Carrera', 'No. 04', 'Flat six', 'Kept in slate grey', 'Glass black, held on the line', 1, 1 ),
);
$pm_day  = 0;
foreach ( $pm_cars as $pm_key => list( $pm_marque, $pm_model, $pm_ref, $pm_spec, $pm_note, $pm_caption, $pm_featured, $pm_order ) ) {
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
	$pm_car  = array(
		'post_type'   => 'pm_car',
		'post_status' => 'publish',
		'post_title'  => "{$pm_marque} {$pm_model}",
		'menu_order'  => $pm_order,
		'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '2026-09-20 10:00:00' ) - $pm_day++ * DAY_IN_SECONDS ),
	);
	if ( $pm_found ) {
		$pm_car['ID'] = $pm_found[0];
	}
	$pm_car_id = wp_insert_post( $pm_car );
	update_post_meta( $pm_car_id, '_pm_seed_key', $pm_key );
	update_post_meta( $pm_car_id, '_pm_demo', 1 );
	foreach ( array(
		'car_image'     => $pm_media[ $pm_key ],
		'car_marque'    => $pm_marque,
		'car_model'     => $pm_model,
		'car_ref'       => $pm_ref,
		'car_spec'      => $pm_spec,
		'car_note'      => $pm_note,
		'car_link'      => '',
		'slide_caption' => $pm_caption,
		'slide_place'   => 'Paphos',
		'car_featured'  => $pm_featured,
	) as $pm_field => $pm_value ) {
		update_field( 'field_pm_' . $pm_field, $pm_value, $pm_car_id );
	}
}
WP_CLI::log( 'Cars: ' . count( $pm_cars ) . '.' );

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

$pm_values_block = panmotors_seed_block(
	'pm/values',
	array(
		'values_title' => 'Our Values',
		'values_link'  => get_page_by_path( 'about' ) ? get_page_by_path( 'about' )->ID : 0,
		'values'       => array(
			array(
				'index' => '01 — Keeping',
				'title' => 'Kept Running',
				'body'  => 'Climate bay, battery care, a circulation drive every month. Nothing in the collection sits still for long.',
			),
			array(
				'index' => '02 — Record',
				'title' => 'Written Down',
				'body'  => 'Every service, every owner, every road. The file matters as much as the car it belongs to.',
			),
			array(
				'index' => '03 — Showing',
				'title' => 'Shown Rarely',
				'body'  => 'One guest at a time, by appointment, with the doors closed and the lights low.',
			),
		),
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

// Synced pattern "Our Values": one source for Home and About.
$pm_pattern = get_posts(
	array(
		'post_type'      => 'wp_block',
		'post_status'    => 'any',
		'meta_key'       => '_pm_seed_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'     => 'values', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		'posts_per_page' => 1,
		'fields'         => 'ids',
	)
);
$pm_pattern_post = array(
	'post_type'    => 'wp_block',
	'post_status'  => 'publish',
	'post_title'   => 'Our Values',
	'post_content' => wp_slash( $pm_values_block ),
);
if ( $pm_pattern ) {
	$pm_pattern_post['ID'] = $pm_pattern[0];
	// The client may have edited the pattern: only write it while it has no values block.
	if ( false !== strpos( get_post_field( 'post_content', $pm_pattern[0] ), '<!-- wp:pm/values' ) && ! getenv( 'PM_SEED_REBUILD' ) ) {
		unset( $pm_pattern_post['post_content'] );
	}
}
$pm_pattern_id = (int) wp_insert_post( $pm_pattern_post );
update_post_meta( $pm_pattern_id, '_pm_seed_key', 'values' );
update_post_meta( $pm_pattern_id, '_pm_demo', 1 );
delete_post_meta( $pm_pattern_id, 'wp_pattern_sync_status' ); // Absent = synced.
$pm_values_ref = serialize_block(
	array(
		'blockName'    => 'core/block',
		'attrs'        => array( 'ref' => $pm_pattern_id ),
		'innerBlocks'  => array(),
		'innerHTML'    => '',
		'innerContent' => array(),
	)
);
WP_CLI::log( "Synced pattern Our Values: {$pm_pattern_id}." );

$pm_hero = static fn( $eyebrow, $intro, $image ) => panmotors_seed_block(
	'pm/page-hero',
	array(
		'page_eyebrow'    => $eyebrow,
		'page_intro'      => $intro,
		'page_hero_image' => $image,
	)
);

$pm_pages = array(
	'featured' => panmotors_seed_block_page(
		'featured-cars',
		'Featured Cars',
		implode(
			"\n\n",
			array(
				$pm_hero( 'The Paphos collection', 'A rotating selection of luxury and performance cars, prepared and presented in our Paphos showroom.', $pm_media['black_studio'] ),
				panmotors_seed_paragraphs( '<p>Every car on this page has been chosen, prepared and photographed at Pan Motors in Paphos. The selection changes as cars arrive and leave, so it shows the collection as it is today rather than a catalogue.</p><p>To see a car in person, arrange a private viewing at the showroom on Avenue 65 in Mesoyi.</p>' ),
				panmotors_seed_block(
					'pm/featured-cars',
					array(
						'featured_title'  => 'Featured Cars',
						'featured_source' => 'featured',
						'featured_limit'  => 12,
					)
				),
				$pm_cta,
			)
		)
	),
	'about'    => panmotors_seed_block_page(
		'about',
		'About Pan Motors',
		implode(
			"\n\n",
			array(
				$pm_hero( 'Mesoyi, Paphos', 'Sales, service and a boutique under one roof on Avenue 65.', $pm_media['sr_night'] ),
				panmotors_seed_paragraphs( '<p>Sales, service and a boutique sit under one roof, so a car is prepared, presented and looked after by the same people who sold it.</p><p>DRAFT: [Client to add the family story: when Pan Motors started, who runs it today, and how the building on Avenue 65 came to be.]</p>' ),
				$pm_about_block,
				$pm_values_ref,
				$pm_cta,
			)
		)
	),
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
	'showroom' => panmotors_seed_block_page(
		'showroom',
		'The Showroom',
		implode(
			"\n\n",
			array(
				$pm_hero( 'Avenue 65, Mesoyi', 'Paphos, open six days a week. Sales, service and the boutique under one roof.', $pm_media['sr_forecourt'] ),
				panmotors_seed_paragraphs( '<p>The Pan Motors showroom is on Avenue 65 in Mesoyi, Paphos. Cars are presented indoors under controlled light, with the service workshop and the boutique in the same building.</p>' ),
				$pm_showroom_block,
				"<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Getting here</h2>\n<!-- /wp:heading -->",
				panmotors_seed_paragraphs( '<p>DRAFT: From Paphos centre, [client to add the route and approximate driving time to Avenue 65, Mesoyi].</p><p>DRAFT: From Paphos International Airport, [client to add the route and approximate driving time].</p><p>DRAFT: Parking: [client to confirm where visitors park].</p>' ),
				$pm_cta,
			)
		)
	),
	'contact'  => panmotors_seed_block_page(
		'contact',
		'Contact',
		implode(
			"\n\n",
			array(
				$pm_hero( 'Paphos, Cyprus', 'Call, write, or walk in during showroom hours. Someone from the family will answer.', 0 ),
				$pm_enquire_block,
				panmotors_seed_block(
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
				),
			)
		)
	),
);

// The Contact button (header, mobile menu, 404) links here. The only page mapping left (D11).
update_field( 'field_pm_page_contact', $pm_pages['contact'], 'option' );
foreach ( array( 'page_featured', 'page_about', 'page_latest', 'page_showroom', 'page_values', 'page_live' ) as $pm_gone ) {
	delete_option( "options_{$pm_gone}" );
	delete_option( "_options_{$pm_gone}" );
}

// Legal pages on page.php. WordPress's own privacy page is reused if it exists.
$pm_legal_draft = '<p>DRAFT: [Client to supply the %s. The text below this line is a placeholder.]</p><h2>Who we are</h2><p>Pan Motors Ltd, Avenue 65, Mesoyi, Paphos 8060, Cyprus.</p>';
$pm_privacy_id  = panmotors_seed_page( 'privacy-policy', 'Privacy Policy', sprintf( $pm_legal_draft, 'privacy policy' ), (int) get_option( 'wp_page_for_privacy_policy' ) );
$pm_cookie_id   = panmotors_seed_page( 'cookie-policy', 'Cookie Policy', sprintf( $pm_legal_draft, 'cookie policy' ) );
update_option( 'wp_page_for_privacy_policy', $pm_privacy_id );

/*
 * ------------------------------------------------------------------
 * 7. Home: the full design as blocks. The hero cannot be moved or removed.
 * ------------------------------------------------------------------
 */
$pm_home = get_posts(
	array(
		'post_type'      => 'page',
		'title'          => 'Home',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
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
			)
		),
		$pm_values_ref,
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

$pm_front = array(
	'post_type'    => 'page',
	'post_title'   => 'Home',
	'post_status'  => 'publish',
	'post_content' => wp_slash( $pm_home_content ),
);
if ( $pm_home ) {
	$pm_front['ID'] = (int) $pm_home[0];
	// Keep the client's own layout once Home is built from blocks. PM_SEED_REBUILD=1 rewrites it.
	if ( false !== strpos( get_post_field( 'post_content', $pm_home[0] ), '<!-- wp:pm/' ) && ! getenv( 'PM_SEED_REBUILD' ) ) {
		unset( $pm_front['post_content'] );
	}
}
$pm_front_id = (int) wp_insert_post( $pm_front );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $pm_front_id );
update_post_meta( $pm_front_id, '_pm_demo', 1 );
update_post_meta( $pm_front_id, '_wp_page_template', 'default' );
WP_CLI::log( "Home: page {$pm_front_id}, built from blocks." );

// Field data from the per-page model is no longer read by anything (D11).
foreach ( array_merge( array_values( $pm_pages ), array( $pm_front_id ) ) as $pm_page_id ) {
	panmotors_seed_forget_page_fields( $pm_page_id );
}

/*
 * ------------------------------------------------------------------
 * 8. Menus: page links only (D9).
 * ------------------------------------------------------------------
 */

/**
 * Create or rebuild a menu of page links and return its ID.
 *
 * @param string $name  Menu name.
 * @param array  $links Label => page ID.
 * @return int
 */
function panmotors_seed_menu( $name, $links ) {
	$menu    = wp_get_nav_menu_object( $name );
	$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $name );

	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
		wp_delete_post( $item->ID, true );
	}

	$position = 0;
	foreach ( $links as $label => $page_id ) {
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

set_theme_mod(
	'nav_menu_locations',
	array(
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
	)
);

/*
 * ------------------------------------------------------------------
 * 9. Site settings.
 * ------------------------------------------------------------------
 */
update_option( 'blogname', 'Pan Motors' );
update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules( false );
set_theme_mod( 'custom_logo', $pm_media['logo'] );
update_option( 'panmotors_demo_content', gmdate( 'c' ), false );

WP_CLI::success( 'Demo content seeded.' );
