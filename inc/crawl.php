<?php
/**
 * Crawl and GEO (theme-map 9.7): robots.txt, the core sitemap, and /llms.txt. Nothing is a
 * physical file: robots.txt through WordPress's robots_txt filter, llms.txt through a rewrite rule.
 *
 * Settings → Technical (administrators): "Allow AI crawlers" and "llms.txt", both on by default.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

/**
 * The AI crawlers named in robots.txt.
 *
 * @return string[]
 */
function panmotors_ai_crawlers() {
	return array( 'GPTBot', 'OAI-SearchBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended' );
}

/**
 * A Technical switch that is on unless it was saved off.
 *
 * @param string $name Field name.
 * @return bool
 */
function panmotors_switch_on( $name ) {
	$value = get_option( 'options_' . $name, null );
	return null === $value || '' === $value || (bool) $value;
}

/**
 * The sitemap robots.txt points to: the SEO plugin's index when one provides it, else core's.
 *
 * @return string URL, or '' when there is none.
 */
function panmotors_sitemap_url() {
	if ( defined( 'WPSEO_VERSION' ) && class_exists( 'WPSEO_Options' ) && WPSEO_Options::get( 'enable_xml_sitemap' ) ) {
		return home_url( '/sitemap_index.xml' );
	}
	if ( defined( 'RANK_MATH_VERSION' ) && class_exists( '\RankMath\Helper' ) && \RankMath\Helper::is_module_active( 'sitemap' ) ) {
		return home_url( '/sitemap_index.xml' );
	}
	return wp_sitemaps_get_server()->sitemaps_enabled() ? home_url( '/wp-sitemap.xml' ) : '';
}

/**
 * The robots.txt: every crawler may read the site (search engines and, by default, the AI crawlers),
 * wp-admin stays closed, one Sitemap line. When "Allow AI crawlers" is off, the five AI crawlers
 * are shut out. A site set to discourage search engines (Settings → Reading) keeps WordPress's
 * own "Disallow: /".
 *
 * @param string $output    Robots.txt from WordPress and plugins.
 * @param bool   $is_public Whether the site may be indexed.
 * @return string
 */
function panmotors_robots_txt( $output, $is_public ) {
	if ( ! $is_public ) {
		return $output;
	}

	$ai    = panmotors_switch_on( 'allow_ai_crawlers' );
	$lines = array( 'User-agent: *' );
	if ( $ai ) {
		foreach ( panmotors_ai_crawlers() as $bot ) {
			$lines[] = 'User-agent: ' . $bot;
		}
	}
	$lines[] = 'Disallow: /wp-admin/';
	$lines[] = 'Allow: /wp-admin/admin-ajax.php';

	if ( ! $ai ) {
		$lines[] = '';
		foreach ( panmotors_ai_crawlers() as $bot ) {
			$lines[] = 'User-agent: ' . $bot;
		}
		$lines[] = 'Disallow: /';
	}

	$sitemap = panmotors_sitemap_url();
	if ( $sitemap ) {
		$lines[] = '';
		$lines[] = 'Sitemap: ' . $sitemap;
	}

	return implode( "\n", $lines ) . "\n";
}
add_filter( 'robots_txt', 'panmotors_robots_txt', PHP_INT_MAX, 2 ); // Last: SEO plugins add their own lines.

/**
 * Core sitemap: pages and events. No users (there are no author pages), no taxonomies and no posts (the
 * site has no blog); Cars are left out in inc/cars.php.
 *
 * @param WP_Sitemaps_Provider $provider Provider.
 * @param string               $name     Provider name.
 * @return WP_Sitemaps_Provider|false
 */
function panmotors_sitemap_providers( $provider, $name ) {
	return in_array( $name, array( 'users', 'taxonomies' ), true ) ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'panmotors_sitemap_providers', 10, 2 );

/**
 * Post types in the core sitemap: pages and events (the Cars post type is never in it; see also
 * inc/cars.php).
 *
 * @param WP_Post_Type[] $post_types Post types.
 * @return WP_Post_Type[]
 */
function panmotors_sitemap_pages_only( $post_types ) {
	return array_intersect_key(
		$post_types,
		array(
			'page'     => true,
			'pm_event' => true,
		)
	);
}
add_filter( 'wp_sitemaps_post_types', 'panmotors_sitemap_pages_only', 20 );

/**
 * /llms.txt: a rewrite rule to a query var, answered in template_redirect.
 */
function panmotors_llms_rewrite() {
	add_rewrite_rule( '^llms\.txt$', 'index.php?pm_llms=1', 'top' );
}
add_action( 'init', 'panmotors_llms_rewrite' );

/**
 * Register the query var.
 *
 * @param string[] $vars Query vars.
 * @return string[]
 */
function panmotors_llms_query_var( $vars ) {
	$vars[] = 'pm_llms';
	return $vars;
}
add_filter( 'query_vars', 'panmotors_llms_query_var' );

/**
 * No canonical redirect for /llms.txt (WordPress would add a trailing slash).
 *
 * @param string|false $redirect Redirect URL.
 * @return string|false
 */
function panmotors_llms_no_redirect( $redirect ) {
	return get_query_var( 'pm_llms' ) ? false : $redirect;
}
add_filter( 'redirect_canonical', 'panmotors_llms_no_redirect' );

/**
 * Flush rewrite rules when the theme is switched to (the llms.txt rule), not on every load.
 */
function panmotors_flush_rewrites() {
	panmotors_llms_rewrite();
	flush_rewrite_rules( false );
}
add_action( 'after_switch_theme', 'panmotors_flush_rewrites' );

/**
 * The llms.txt text: business facts from the settings, then one line per published page in the
 * menus with its URL and intro, then one line per event (title, date, URL). Markdown, as the llms.txt proposal describes.
 *
 * @return string
 */
function panmotors_llms_text() {
	$name  = trim( (string) panmotors_option( 'trading_name', get_bloginfo( 'name' ) ) );
	$lines = array( '# ' . $name, '' );

	$description = trim( (string) panmotors_option( 'description', '' ) );
	if ( $description ) {
		$lines[] = '> ' . $description;
		$lines[] = '';
	}

	$address = implode(
		', ',
		array_filter(
			array(
				trim( (string) panmotors_option( 'legal_name', '' ) ),
				trim( (string) panmotors_option( 'street_address', '' ) ),
				trim( (string) panmotors_option( 'locality', '' ) ),
				trim( panmotors_option( 'city', '' ) . ' ' . panmotors_option( 'postcode', '' ) ),
				trim( (string) panmotors_option( 'country_name', '' ) ),
			)
		)
	);
	$phones  = array_filter( array_map( static fn( $row ) => trim( (string) ( $row['number'] ?? '' ) ), panmotors_rows( 'phones', 'option' ) ) );
	$email   = trim( (string) panmotors_option( 'email', '' ) );
	$contact = array_filter(
		array(
			$address ? '- Address: ' . $address : '',
			$phones ? '- Phone: ' . implode( ' / ', $phones ) : '',
			$email ? '- Email: ' . $email : '',
			'- Website: ' . home_url( '/' ),
		)
	);
	$lines   = array_merge( $lines, array( '## Contact', '' ), $contact, array( '' ) );

	$hours = array();
	foreach ( panmotors_rows( 'hours', 'option' ) as $row ) {
		$day  = trim( (string) ( $row['day'] ?? '' ) );
		$time = trim( (string) ( $row['time'] ?? '' ) );
		if ( $day && $time ) {
			$hours[] = '- ' . $day . ': ' . $time;
		}
	}
	if ( $hours ) {
		$lines = array_merge( $lines, array( '## Opening hours', '' ), $hours, array( '' ) );
	}

	$pages = array();
	foreach ( array( 'primary', 'footer' ) as $location ) {
		$menus = get_nav_menu_locations();
		$items = ! empty( $menus[ $location ] ) ? (array) wp_get_nav_menu_items( $menus[ $location ] ) : array();
		foreach ( $items as $item ) {
			$id = (int) $item->object_id;
			if ( 'page' !== $item->object || isset( $pages[ $id ] ) || 'publish' !== get_post_status( $id ) ) {
				continue;
			}
			$intro        = panmotors_page_description( $id );
			$intro        = trim( (string) panmotors_option( 'description', '' ) ) === $intro ? '' : $intro;
			$pages[ $id ] = '- [' . get_the_title( $id ) . '](' . get_permalink( $id ) . ')' . ( $intro ? ': ' . $intro : '' );
		}
	}
	if ( $pages ) {
		$lines = array_merge( $lines, array( '## Pages', '' ), array_values( $pages ), array( '' ) );
	}

	// Events: one line each (title, date, URL), upcoming first, then past.
	$events = panmotors_events();
	foreach ( array(
		'upcoming' => '## Upcoming events',
		'past'     => '## Past events',
	) as $group => $heading ) {
		$rows = array();
		foreach ( $events[ $group ] as $event ) {
			$date   = wp_strip_all_tags( panmotors_event_date_html( $event ) );
			$rows[] = '- [' . $event['title'] . '](' . $event['url'] . '): ' . $date . ( 'scheduled' !== $event['status'] ? ' (' . $event['pill'] . ')' : '' );
		}
		if ( $rows ) {
			$lines = array_merge( $lines, array( $heading, '' ), $rows, array( '' ) );
		}
	}

	return html_entity_decode( implode( "\n", $lines ), ENT_QUOTES, 'UTF-8' );
}

/**
 * Answer /llms.txt: plain text, UTF-8. When the Technical switch is off, it does not exist (404).
 */
function panmotors_llms_serve() {
	if ( ! get_query_var( 'pm_llms' ) ) {
		return;
	}
	if ( ! panmotors_switch_on( 'llms_txt' ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		return;
	}
	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Content-Type-Options: nosniff' );
	echo panmotors_llms_text(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text response.
	exit;
}
add_action( 'template_redirect', 'panmotors_llms_serve' );

/**
 * Rank Math lists a post type in its sitemap only when its Sitemap setting for that type is on,
 * and a post type registered after Rank Math was set up has no setting. Events are in the sitemap
 * unless that setting was saved off (Rank Math → Sitemap Settings → Events). Cars are not public,
 * so no SEO plugin lists them.
 *
 * @param mixed $settings Rank Math sitemap settings.
 * @return mixed
 */
function panmotors_rank_math_events_sitemap( $settings ) {
	if ( is_array( $settings ) && ! isset( $settings['pt_pm_event_sitemap'] ) ) {
		$settings['pt_pm_event_sitemap'] = 'on';
	}
	return $settings;
}
add_filter( 'option_rank-math-options-sitemap', 'panmotors_rank_math_events_sitemap' );

/**
 * Rank Math reads its settings once, before the theme loads: read them again so the filter above
 * applies.
 */
function panmotors_rank_math_settings_reload() {
	if ( function_exists( 'rank_math' ) && isset( rank_math()->settings ) && method_exists( rank_math()->settings, 'reset' ) && null === rank_math()->settings->get( 'sitemap.pt_pm_event_sitemap', null ) ) {
		rank_math()->settings->reset();
	}
}
add_action( 'after_setup_theme', 'panmotors_rank_math_settings_reload' );
