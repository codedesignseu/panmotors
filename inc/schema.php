<?php
/**
 * JSON-LD structured data (theme-map 9.3, pages.md §5): one @graph in <head> on every page, built
 * from Pan Motors settings and the page itself. Nothing is hardcoded; empty values are left out.
 *
 * - AutoDealer (#business): the business facts from the settings, never car data (pm_car).
 * - WebSite (#website), published by #business.
 * - The current page (WebPage, AboutPage or ContactPage), its BreadcrumbList on inner pages, and
 *   FAQPage where the page has questions, with the text exactly as the page shows it.
 * - With Yoast SEO or Rank Math active, their own schema is switched off, so every page has one
 *   business entity.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

/**
 * The questions and answers of the pm/faq blocks on a page, exactly as the page prints them
 * (template-parts/sections/faq.php): rows with both a question and an answer, trimmed, in order.
 * Hidden blocks (Hide on the block toolbar) are left out. For the FAQPage node of the @graph
 * (TASKS §5); nothing prints JSON-LD yet.
 *
 * @param int $post_id Page ID. Defaults to the current page.
 * @return array[] Items: question, answer.
 */
function panmotors_faq_items( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	$content = $post_id ? (string) get_post_field( 'post_content', $post_id ) : '';
	if ( false === strpos( $content, '<!-- wp:pm/faq' ) ) {
		return array();
	}

	$items = array();
	$walk  = static function ( $blocks ) use ( &$walk, &$items ) {
		foreach ( $blocks as $block ) {
			if ( false === ( $block['attrs']['metadata']['blockVisibility'] ?? null ) ) {
				continue;
			}
			if ( 'pm/faq' === $block['blockName'] ) {
				$count = (int) panmotors_block_field( $block, 'faqs' );
				for ( $i = 0; $i < $count; $i++ ) {
					$question = trim( (string) panmotors_block_field( $block, "faqs_{$i}_question" ) );
					$answer   = trim( (string) panmotors_block_field( $block, "faqs_{$i}_answer" ) );
					if ( '' !== $question && '' !== $answer ) {
						$items[] = array(
							'question' => $question,
							'answer'   => $answer,
						);
					}
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$walk( $block['innerBlocks'] );
			}
		}
	};
	$walk( parse_blocks( $content ) );
	return $items;
}

/**
 * Remove every empty value from a schema array, recursively: null, '', empty arrays, and nodes
 * that are left with nothing but their @type. A value that is not set never reaches the JSON-LD.
 *
 * @param mixed $value Value.
 * @return mixed Cleaned value, or null when empty.
 */
function panmotors_schema_clean( $value ) {
	if ( is_array( $value ) ) {
		$clean = array();
		foreach ( $value as $key => $item ) {
			$item = panmotors_schema_clean( $item );
			if ( null !== $item ) {
				$clean[ $key ] = $item;
			}
		}
		if ( ! $clean || array( '@type' ) === array_keys( $clean ) ) {
			return null;
		}
		return array_is_list( $value ) ? array_values( $clean ) : $clean;
	}
	if ( is_string( $value ) ) {
		$value = trim( $value );
		return '' === $value ? null : $value;
	}
	return $value;
}

/**
 * The business node (AutoDealer, #business), from Pan Motors settings only. No car data (pm_car)
 * and no photos from other pages: images come from Settings → Business → Business photos.
 *
 * @return array
 */
function panmotors_schema_business() {
	$home = home_url( '/' );

	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$logo    = $logo_id ? wp_get_attachment_image_src( $logo_id, 'full' ) : false;

	$images = array();
	foreach ( panmotors_rows( 'business_photos', 'option' ) as $photo ) {
		$src = wp_get_attachment_image_src( (int) $photo, 'full' );
		if ( $src ) {
			$images[] = $src[0];
		}
	}

	$phones = array_values( array_filter( array_map( static fn( $row ) => trim( (string) ( $row['number'] ?? '' ) ), panmotors_rows( 'phones', 'option' ) ) ) );

	$lat = panmotors_option( 'latitude', '' );
	$lng = panmotors_option( 'longitude', '' );
	$geo = ( is_numeric( $lat ) && is_numeric( $lng ) ) ? array(
		'@type'     => 'GeoCoordinates',
		'latitude'  => (float) $lat,
		'longitude' => (float) $lng,
	) : null;

	$hours = array();
	foreach ( panmotors_rows( 'hours', 'option' ) as $row ) {
		$days  = array_values( array_filter( (array) ( $row['days'] ?? array() ) ) );
		$opens = substr( (string) ( $row['opens'] ?? '' ), 0, 5 );
		$close = substr( (string) ( $row['closes'] ?? '' ), 0, 5 );
		if ( $days && $opens && $close ) {
			$hours[] = array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => array_map( static fn( $day ) => 'https://schema.org/' . $day, $days ),
				'opens'     => $opens,
				'closes'    => $close,
			);
		}
	}

	$same_as = array_merge(
		array(
			panmotors_option( 'instagram_url', '' ),
			panmotors_option( 'facebook_url', '' ),
			panmotors_option( 'google_business_url', '' ),
		),
		array_map( static fn( $row ) => (string) ( $row['url'] ?? '' ), panmotors_rows( 'other_profiles', 'option' ) )
	);

	$brands = array();
	foreach ( panmotors_rows( 'marques', 'option' ) as $row ) {
		$brands[] = array(
			'@type' => 'Brand',
			'name'  => (string) ( $row['name'] ?? '' ),
		);
	}

	$street = implode( ', ', array_filter( array( trim( (string) panmotors_option( 'street_address', '' ) ), trim( (string) panmotors_option( 'locality', '' ) ) ) ) );

	return array(
		'@type'                     => 'AutoDealer',
		'@id'                       => $home . '#business',
		'name'                      => panmotors_option( 'trading_name', '' ),
		'legalName'                 => panmotors_option( 'legal_name', '' ),
		'description'               => panmotors_option( 'description', '' ),
		'url'                       => $home,
		'logo'                      => $logo ? array(
			'@type'  => 'ImageObject',
			'@id'    => $home . '#logo',
			'url'    => $logo[0],
			'width'  => (int) $logo[1],
			'height' => (int) $logo[2],
		) : null,
		'image'                     => $images,
		'telephone'                 => $phones[0] ?? '',
		'email'                     => panmotors_option( 'email', '' ),
		'address'                   => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $street,
			'addressLocality' => panmotors_option( 'city', '' ),
			'postalCode'      => panmotors_option( 'postcode', '' ),
			'addressCountry'  => panmotors_option( 'country', '' ),
		),
		'geo'                       => $geo,
		'hasMap'                    => panmotors_option( 'map_url', '' ),
		'openingHoursSpecification' => $hours,
		'sameAs'                    => array_values( array_unique( array_filter( array_map( 'trim', $same_as ) ) ) ),
		'brand'                     => $brands,
		'areaServed'                => array(
			array(
				'@type' => 'City',
				'name'  => panmotors_option( 'city', '' ),
			),
			array(
				'@type' => 'Country',
				'name'  => panmotors_option( 'country_name', '' ),
			),
		),
		'foundingDate'              => (string) panmotors_option( 'founded_year', '' ),
	);
}

/**
 * The page's description, the same text as the meta description: the page header intro (or the
 * older page top intro), else the one-sentence business description.
 *
 * @param int $post_id Page ID, 0 for none.
 * @return string
 */
function panmotors_page_description( $post_id ) {
	$text = '';
	if ( $post_id && (int) get_option( 'page_on_front' ) !== $post_id ) {
		$text = (string) panmotors_block_field( panmotors_find_block( $post_id, 'pm/page-header' ), 'header_intro' );
		$text = $text ? $text : (string) panmotors_block_field( panmotors_find_block( $post_id, 'pm/page-hero' ), 'page_intro' );
	}
	$text = $text ? $text : (string) panmotors_option( 'description', '' );
	return trim( wp_strip_all_tags( $text ) );
}

/**
 * The page's main image for primaryImageOfPage: the homepage hero poster, or the photo of a Page
 * header in the Photo style.
 *
 * @param int $post_id Page ID.
 * @return array|null ImageObject.
 */
function panmotors_schema_page_image( $post_id ) {
	if ( (int) get_option( 'page_on_front' ) === $post_id ) {
		$image_id = panmotors_hero_poster_id();
	} else {
		$header   = panmotors_find_block( $post_id, 'pm/page-header' );
		$image_id = 'image' === panmotors_block_field( $header, 'header_style' ) ? (int) panmotors_block_field( $header, 'header_image' ) : 0;
	}
	$src = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : false;
	return $src ? array(
		'@type'  => 'ImageObject',
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
	) : null;
}

/**
 * The schema type of a page: Page settings → Type for search engines, else ContactPage for the
 * page the Contact button opens, else WebPage.
 *
 * @param int $post_id Page ID.
 * @return string
 */
function panmotors_schema_page_type( $post_id ) {
	$types = array(
		'about'   => 'AboutPage',
		'contact' => 'ContactPage',
		'web'     => 'WebPage',
	);
	$set = (string) panmotors_field( 'schema_type', $post_id, 'auto' );
	if ( isset( $types[ $set ] ) ) {
		return $types[ $set ];
	}
	return panmotors_contact_page() === $post_id ? 'ContactPage' : 'WebPage';
}

/**
 * The whole @graph for the current request: the business, the website, and on a page its
 * WebPage, BreadcrumbList (inner pages) and FAQPage (pages with questions). Empty values are left
 * out (panmotors_schema_clean()).
 *
 * @return array[]
 */
function panmotors_schema_graph() {
	$home  = home_url( '/' );
	$graph = array(
		panmotors_schema_business(),
		array(
			'@type'      => 'WebSite',
			'@id'        => $home . '#website',
			'url'        => $home,
			'name'       => panmotors_option( 'trading_name', get_bloginfo( 'name' ) ),
			'inLanguage' => get_bloginfo( 'language' ),
			'publisher'  => array( '@id' => $home . '#business' ),
		),
	);

	$post_id = ( is_page() || is_front_page() ) ? (int) get_queried_object_id() : 0;
	if ( $post_id ) {
		$url      = get_permalink( $post_id );
		$is_home  = (int) get_option( 'page_on_front' ) === $post_id;
		$faqs     = panmotors_faq_items( $post_id );
		$crumbs   = $is_home ? null : array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $url . '#breadcrumb',
			'itemListElement' => array(
				array(
					'@type'    => 'ListItem',
					'position' => 1,
					'name'     => get_the_title( (int) get_option( 'page_on_front' ) ),
					'item'     => $home,
				),
				array(
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => get_the_title( $post_id ),
					'item'     => $url,
				),
			),
		);
		$graph[] = array(
			'@type'              => panmotors_schema_page_type( $post_id ),
			'@id'                => $url . '#webpage',
			'url'                => $url,
			'name'               => wp_get_document_title(),
			'description'        => panmotors_page_description( $post_id ),
			'inLanguage'         => get_bloginfo( 'language' ),
			'isPartOf'           => array( '@id' => $home . '#website' ),
			'about'              => array( '@id' => $home . '#business' ),
			'primaryImageOfPage' => panmotors_schema_page_image( $post_id ),
			'breadcrumb'         => $crumbs ? array( '@id' => $url . '#breadcrumb' ) : null,
		);
		if ( $crumbs ) {
			$graph[] = $crumbs;
		}
		if ( $faqs ) {
			$graph[] = array(
				'@type'      => 'FAQPage',
				'@id'        => $url . '#faq',
				'isPartOf'   => array( '@id' => $url . '#webpage' ),
				'mainEntity' => array_map(
					static fn( $faq ) => array(
						'@type'          => 'Question',
						'name'           => $faq['question'],
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => $faq['answer'],
						),
					),
					$faqs
				),
			);
		}
	}

	return array_values( array_filter( array_map( 'panmotors_schema_clean', $graph ) ) );
}

/**
 * Print the @graph once in <head>, on every front-end page.
 */
function panmotors_schema_print() {
	$graph = panmotors_schema_graph();
	if ( ! $graph ) {
		return;
	}
	printf(
		"<script type=\"application/ld+json\">%s</script>\n",
		wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
		)
	);
}
add_action( 'wp_head', 'panmotors_schema_print', 20 );

/**
 * One business entity per page: when Yoast SEO or Rank Math is active, their own schema output is
 * turned off with their documented filters, and the theme's graph stays the only one. The plugins
 * keep titles, meta descriptions, canonical, Open Graph and the sitemap.
 */
add_filter( 'wpseo_json_ld_output', '__return_false' );
add_filter( 'rank_math/json_ld', '__return_empty_array', 99 );

/**
 * Whether an SEO plugin that writes titles and meta descriptions is active.
 *
 * @return bool
 */
function panmotors_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}
