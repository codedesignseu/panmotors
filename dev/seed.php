<?php
/**
 * Demo content seed. Development only, excluded from deploys via .distignore.
 *
 * Run from Local's site shell, in the theme folder:
 *   wp eval-file dev/seed.php
 *
 * What it does:
 * - Imports _design/uploads into the media library with descriptive file names,
 *   titles, alt text and captions (theme-map 9.5).
 * - Syncs the acf-json field groups into the database so they are editable in wp-admin.
 * - Fills the Pan Motors options page and the homepage fields with the _design data.
 * - Creates the Primary and Footer menus and assigns them.
 * - Sets a static front page, the site title and the custom logo.
 *
 * Safe to re-run: media is matched on the original file name, fields are overwritten.
 * Everything it creates is flagged with the `_pm_demo` meta / `panmotors_demo_content` option
 * so it can be found and removed before launch.
 *
 * @package panmotors
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run with: wp eval-file dev/seed.php\n" );
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
 * 1. Field groups: import acf-json into the database if not there yet.
 * ------------------------------------------------------------------
 */
foreach ( glob( get_template_directory() . '/acf-json/group_*.json' ) as $pm_json ) {
	$pm_group = json_decode( file_get_contents( $pm_json ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! $pm_group || acf_get_field_group_post( $pm_group['key'] ) ) {
		continue;
	}
	acf_import_field_group( $pm_group );
	WP_CLI::log( "Imported field group: {$pm_group['title']}" );
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
	'footer_copyright'       => 'Pan Motors',
);

foreach ( $pm_options as $pm_name => $pm_value ) {
	update_field( 'field_pm_' . $pm_name, $pm_value, 'option' );
}
WP_CLI::log( 'Options page filled.' );

/*
 * ------------------------------------------------------------------
 * 4. Front page.
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

$pm_front_id = $pm_home ? (int) $pm_home[0] : wp_insert_post(
	array(
		'post_type'   => 'page',
		'post_title'  => 'Home',
		'post_status' => 'publish',
	)
);

wp_update_post(
	array(
		'ID'          => $pm_front_id,
		'post_status' => 'publish',
	)
);
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $pm_front_id );

$pm_front = array(
	// Hero.
	'hero_eyebrow'     => 'Pan Motors, luxury car boutique in Paphos, Cyprus',
	'hero_title'       => "Luxury\nin Motion",
	'hero_video'       => $pm_media['v_hero'],
	'hero_poster'      => $pm_media['red_night'],
	'hero_cta_label'   => 'View the cars',
	'hero_cta_link'    => '#floor',

	// Featured Cars.
	'featured_title'   => 'Featured Cars',
	'featured_intro'   => 'A rotating selection of luxury and performance cars, prepared and presented in our Paphos showroom.',
	'featured_cars'    => array(
		array(
			'image'      => $pm_media['porsche_studio'],
			'marque'     => 'Porsche',
			'model_name' => '911 Carrera',
			'ref_no'     => 'No. 04',
			'spec'       => 'Flat six',
			'note'       => 'Kept in slate grey',
			'link'       => '',
		),
		array(
			'image'      => $pm_media['grey_sunset'],
			'marque'     => 'Grand Touring',
			'model_name' => 'Mid-Engine Coupé',
			'ref_no'     => 'No. 09',
			'spec'       => 'Twin-turbo V8',
			'note'       => 'Last light',
			'link'       => '',
		),
		array(
			'image'      => $pm_media['ferrari_rear'],
			'marque'     => 'Ferrari',
			'model_name' => '458 Italia',
			'ref_no'     => 'No. 12',
			'spec'       => 'V8',
			'note'       => 'Single owner',
			'link'       => '',
		),
		array(
			'image'      => $pm_media['black_studio'],
			'marque'     => 'Track',
			'model_name' => 'Winged Coupé',
			'ref_no'     => 'No. 17',
			'spec'       => 'Naturally aspirated',
			'note'       => 'Carbon aero',
			'link'       => '',
		),
	),

	// Our Values.
	'values_title'     => 'Our Values',
	'values'           => array(
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

	// About. The options description is printed first; this continues it without repeating it.
	'about_image'      => $pm_media['mclaren'],
	'about_eyebrow'    => 'Mesoyi, Paphos',
	'about_title'      => 'About Pan Motors',
	'about_text'       => '<p>Sales, service and a boutique sit under one roof, so a car is prepared, presented and looked after by the same people who sold it.</p>',
	'about_stats'      => array(
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

	// Latest Cars. Every place is Paphos (D5).
	'latest_eyebrow'   => 'Latest arrivals',
	'latest_title'     => 'Latest Cars',
	'latest_cars'      => array(
		array(
			'image'   => $pm_media['mclaren'],
			'caption' => 'Bay four, morning light',
			'place'   => 'Paphos',
		),
		array(
			'image'   => $pm_media['red_night'],
			'caption' => 'Night run, empty ring road',
			'place'   => 'Paphos',
		),
		array(
			'image'   => $pm_media['black_studio'],
			'caption' => 'Single lamp, no reflectors',
			'place'   => 'Paphos',
		),
		array(
			'image'   => $pm_media['grey_sunset'],
			'caption' => 'After the rain, last light',
			'place'   => 'Paphos',
		),
		array(
			'image'   => $pm_media['ferrari_rear'],
			'caption' => 'Rear three-quarter, no. 458',
			'place'   => 'Paphos',
		),
		array(
			'image'   => $pm_media['porsche_studio'],
			'caption' => 'Glass black, held on the line',
			'place'   => 'Paphos',
		),
	),

	// Pan Motors Live.
	'live_eyebrow'     => 'Social',
	'live_title'       => 'Pan Motors Live',
	'live_cta_label'   => 'Follow the floor',
	'live_posts'       => array(
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

	// Showroom. Captions come from the attachments.
	'showroom_eyebrow' => 'Avenue 65, Mesoyi',
	'showroom_title'   => 'The Showroom',
	'showroom_intro'   => 'Paphos, open six days a week. Sales, service and the boutique under one roof.',
	'showroom_photos'  => array( $pm_media['sr_night'], $pm_media['sr_bay'], $pm_media['sr_floor'], $pm_media['sr_forecourt'] ),

	// Questions (theme-map 9.4). Draft answers for the client to confirm.
	'faq_title'        => 'Questions',
	'faqs'             => array(
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

	// Enquire.
	'enquire_title'    => 'Come And See',
	'enquire_intro'    => 'Call, write, or walk in during showroom hours. Someone from the family will answer.',
);

foreach ( $pm_front as $pm_name => $pm_value ) {
	update_field( 'field_pm_' . $pm_name, $pm_value, $pm_front_id );
}
WP_CLI::log( "Homepage fields filled on page {$pm_front_id}." );

/*
 * ------------------------------------------------------------------
 * 5. Menus. Links use home_url( '/#…' ) so they also work from inner pages.
 * ------------------------------------------------------------------
 */

/**
 * Create or rebuild a menu of custom links and return its ID.
 *
 * @param string   $name  Menu name.
 * @param string[] $links Label => anchor.
 * @return int
 */
function panmotors_seed_menu( $name, $links ) {
	$menu    = wp_get_nav_menu_object( $name );
	$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $name );

	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
		wp_delete_post( $item->ID, true );
	}

	$position = 0;
	foreach ( $links as $label => $anchor ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'    => $label,
				'menu-item-url'      => home_url( '/' . $anchor ),
				'menu-item-type'     => 'custom',
				'menu-item-status'   => 'publish',
				'menu-item-position' => ++$position,
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
				'Featured Cars'    => '#floor',
				'Our Values'       => '#ways',
				'About Pan Motors' => '#heritage',
				'Latest Cars'      => '#gallery',
				'Live'             => '#live',
				'Showroom'         => '#showroom',
			)
		),
		'footer'  => panmotors_seed_menu(
			'Footer',
			array(
				'Featured Cars' => '#floor',
				'Showroom'      => '#showroom',
				'Contact'       => '#enquire',
			)
		),
	)
);

/*
 * ------------------------------------------------------------------
 * 6. Site settings.
 * ------------------------------------------------------------------
 */
update_option( 'blogname', 'Pan Motors' );
set_theme_mod( 'custom_logo', $pm_media['logo'] );
update_post_meta( $pm_front_id, '_pm_demo', 1 );
update_option( 'panmotors_demo_content', gmdate( 'c' ), false );

WP_CLI::success( 'Demo content seeded.' );
