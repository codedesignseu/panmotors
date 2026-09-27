<?php
/**
 * Global design settings (D12, docs/inner-pages.md §1): Pan Motors → Design.
 *
 * Colours, fonts, heading scale, body size, corner radius, logo heights, the logo for light
 * backgrounds and the default share image. The values print once as a small inline style after
 * main.css, on the front end and in the block editor canvas, so the editor matches the site.
 * main.css holds the same defaults, so the site looks as designed without any saved settings.
 *
 * Administrators edit the tab. Editors see it only when Technical → "Editors can change the
 * design" is on.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

/**
 * unicode-range of the Fontsource latin and latin-ext subsets.
 */
const PANMOTORS_RANGE_LATIN     = 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD';
const PANMOTORS_RANGE_LATIN_EXT = 'U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF';

/**
 * The bundled fonts (assets/fonts/, Fontsource woff2, OFL). Files are {prefix}-{subset}-{suffix}-{style}.woff2.
 *
 * @return array[] Keyed by the value of the Heading font / Body font select.
 */
function panmotors_fonts() {
	return array(
		'bodoni-moda'        => array( 'Bodoni Moda', 'serif', '400 900', 'bodoni-moda', 'opsz', array( 'normal', 'italic' ) ),
		'playfair-display'   => array( 'Playfair Display', 'serif', '400 900', 'playfair-display', 'wght', array( 'normal', 'italic' ) ),
		'cormorant-garamond' => array( 'Cormorant Garamond', 'serif', '300 700', 'cormorant-garamond', 'wght', array( 'normal', 'italic' ) ),
		'dm-serif-display'   => array( 'DM Serif Display', 'serif', '400', 'dm-serif-display', '400', array( 'normal', 'italic' ) ),
		'archivo'            => array( 'Archivo', 'sans-serif', '100 900', 'archivo', 'wght', array( 'normal' ) ),
		'inter'              => array( 'Inter', 'sans-serif', '100 900', 'inter', 'wght', array( 'normal' ) ),
		'manrope'            => array( 'Manrope', 'sans-serif', '200 800', 'manrope', 'wght', array( 'normal' ) ),
		'dm-sans'            => array( 'DM Sans', 'sans-serif', '100 1000', 'dm-sans', 'wght', array( 'normal' ) ),
	);
}

/**
 * The design defaults: the values of the original design, and of main.css :root.
 *
 * @return array
 */
function panmotors_design_defaults() {
	return array(
		'ink'           => '#0c0b0b',
		'paper'         => '#f3f2f2',
		'accent'        => '#ec3013',
		'surface'       => '#161514',
		'font_heading'  => 'bodoni-moda',
		'font_body'     => 'archivo',
		'heading_scale' => 100,
		'body_size'     => 16,
		'radius'        => 24,
		'logo_h'        => 46,
		'logo_h_m'      => 46,
	);
}

/**
 * The saved design settings, checked and filled with the defaults.
 *
 * @return array
 */
function panmotors_design() {
	static $design = null;
	if ( null !== $design ) {
		return $design;
	}

	$design = panmotors_design_defaults();

	foreach ( array( 'ink', 'paper', 'accent', 'surface' ) as $name ) {
		$value = strtolower( (string) panmotors_option( 'color_' . $name, '' ) );
		if ( preg_match( '/^#[0-9a-f]{6}$/', $value ) ) {
			$design[ $name ] = $value;
		}
	}

	$fonts = panmotors_fonts();
	foreach ( array( 'heading', 'body' ) as $slot ) {
		$key = (string) panmotors_option( 'font_' . $slot, '' );
		if ( 'custom' === $key && panmotors_custom_font_url( $slot, 'regular' ) ) {
			$design[ 'font_' . $slot ] = 'custom';
		} elseif ( isset( $fonts[ $key ] ) ) {
			$design[ 'font_' . $slot ] = $key;
		}
	}

	$ranges = array(
		'heading_scale' => array( 'heading_scale', 85, 115 ),
		'body_size'     => array( 'body_size', 14, 18 ),
		'radius'        => array( 'radius', 0, 48 ),
		'logo_h'        => array( 'logo_height', 24, 80 ),
		'logo_h_m'      => array( 'logo_height_mobile', 24, 80 ),
	);
	foreach ( $ranges as $name => list( $field, $min, $max ) ) {
		$value = panmotors_option( $field, null );
		if ( is_numeric( $value ) ) {
			$design[ $name ] = max( $min, min( $max, (int) $value ) );
		}
	}

	return $design;
}

/**
 * URL of an uploaded custom font file.
 *
 * @param string $slot  'heading' or 'body'.
 * @param string $style 'regular' or 'italic'.
 * @return string URL, or '' when there is no woff2 file.
 */
function panmotors_custom_font_url( $slot, $style ) {
	$id  = (int) panmotors_option( 'font_' . $slot . '_' . $style, 0 );
	$url = $id ? (string) wp_get_attachment_url( $id ) : '';
	return ( $url && 'woff2' === strtolower( pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ) ) ? $url : '';
}

/**
 * The @font-face rules and CSS family value of the font in one slot.
 *
 * @param string $slot 'heading' or 'body'.
 * @return array { faces: string, family: string, preload: string[] }
 */
function panmotors_font_css( $slot ) {
	$key   = panmotors_design()[ 'font_' . $slot ];
	$faces = '';

	if ( 'custom' === $key ) {
		$name    = 'heading' === $slot ? 'PM Heading' : 'PM Body';
		$generic = 'heading' === $slot ? 'serif' : 'sans-serif';
		$preload = array();
		foreach ( array( 'regular' => 'normal', 'italic' => 'italic' ) as $style => $css_style ) {
			$url = panmotors_custom_font_url( $slot, $style );
			if ( $url ) {
				$faces .= sprintf( "@font-face{font-family:'%s';font-style:%s;font-weight:100 900;font-display:swap;src:url(%s) format('woff2')}", $name, $css_style, esc_url( $url ) );
				if ( 'regular' === $style ) {
					$preload[] = $url;
				}
			}
		}
		return array( 'faces' => $faces, 'family' => "'" . $name . "', " . $generic, 'preload' => $preload );
	}

	list( $name, $generic, $weight, $prefix, $suffix, $styles ) = panmotors_fonts()[ $key ];
	foreach ( $styles as $style ) {
		foreach ( array( 'latin' => PANMOTORS_RANGE_LATIN, 'latin-ext' => PANMOTORS_RANGE_LATIN_EXT ) as $subset => $range ) {
			$faces .= sprintf(
				"@font-face{font-family:'%s';font-style:%s;font-weight:%s;font-display:swap;src:url(%s) format('woff2');unicode-range:%s}",
				$name,
				$style,
				$weight,
				esc_url( PANMOTORS_URI . "/assets/fonts/{$prefix}-{$subset}-{$suffix}-{$style}.woff2" ),
				$range
			);
		}
	}
	// Latin, upright only: latin-ext and italics load on demand through unicode-range and font-style.
	$preload = array( PANMOTORS_URI . "/assets/fonts/{$prefix}-latin-{$suffix}-normal.woff2" );

	return array( 'faces' => $faces, 'family' => "'" . $name . "', " . $generic, 'preload' => $preload );
}

/**
 * "r g b" channels of a #rrggbb colour, for rgb(var(--ink-rgb) / alpha) gradient stops.
 *
 * @param string $hex Colour.
 * @return string
 */
function panmotors_hex_channels( $hex ) {
	return implode( ' ', array_map( 'hexdec', str_split( ltrim( $hex, '#' ), 2 ) ) );
}

/**
 * The inline design CSS: @font-face for the two chosen fonts, then the tokens.
 *
 * @return string
 */
function panmotors_design_css() {
	$d       = panmotors_design();
	$heading = panmotors_font_css( 'heading' );
	$body    = panmotors_font_css( 'body' );

	$tokens = array(
		'--ink'          => $d['ink'],
		'--paper'        => $d['paper'],
		'--accent'       => $d['accent'],
		'--surface'      => $d['surface'],
		'--ink-rgb'      => panmotors_hex_channels( $d['ink'] ),
		'--paper-rgb'    => panmotors_hex_channels( $d['paper'] ),
		'--r'            => $d['radius'] . 'px',
		'--font-display' => $heading['family'],
		'--font-body'    => $body['family'],
		'--h-scale'      => rtrim( rtrim( number_format( $d['heading_scale'] / 100, 2, '.', '' ), '0' ), '.' ),
		'--fs-body'      => $d['body_size'] . 'px',
		'--logo-h'       => $d['logo_h'] . 'px',
		'--logo-h-m'     => $d['logo_h_m'] . 'px',
	);

	$root = '';
	foreach ( $tokens as $name => $value ) {
		$root .= $name . ':' . $value . ';';
	}

	return $heading['faces'] . $body['faces'] . ':root{' . $root . '}';
}

/**
 * Attach the design CSS to main.css, once per stylesheet queue: the front end
 * (wp_enqueue_scripts) and the block editor canvas (enqueue_block_assets, which the editor runs
 * with a fresh queue for the iframe).
 */
function panmotors_design_inline_style() {
	if ( ! wp_style_is( 'pm-main', 'enqueued' ) || wp_styles()->get_data( 'pm-main', 'after' ) ) {
		return;
	}
	wp_add_inline_style( 'pm-main', panmotors_design_css() );
}
add_action( 'wp_enqueue_scripts', 'panmotors_design_inline_style', 20 );
add_action( 'enqueue_block_assets', 'panmotors_design_inline_style', 20 );

/**
 * Font files preloaded in <head>: the latin upright file of the heading and body fonts.
 *
 * @return string[] URLs.
 */
function panmotors_preload_fonts() {
	return array_merge( panmotors_font_css( 'body' )['preload'], panmotors_font_css( 'heading' )['preload'] );
}

/**
 * WCAG 2 contrast ratio of two #rrggbb colours.
 *
 * @param string $a Colour.
 * @param string $b Colour.
 * @return float 1 to 21.
 */
function panmotors_contrast( $a, $b ) {
	$lum = static function ( $hex ) {
		$l = array();
		foreach ( str_split( ltrim( $hex, '#' ), 2 ) as $i => $pair ) {
			$c     = hexdec( $pair ) / 255;
			$l[ $i ] = $c <= 0.04045 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		}
		return 0.2126 * $l[0] + 0.7152 * $l[1] + 0.0722 * $l[2];
	};
	$la = $lum( $a );
	$lb = $lum( $b );
	return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
}

/**
 * The colour pairs the design relies on for text, with their contrast (WCAG AA: 4.5 for text).
 *
 * @return array[] { label: string, ratio: float, pass: bool }
 */
function panmotors_contrast_checks() {
	$d     = panmotors_design();
	$pairs = array(
		array( __( 'Accent on the dark colour', 'panmotors' ), $d['accent'], $d['ink'] ),
		array( __( 'Dark colour on the light colour', 'panmotors' ), $d['ink'], $d['paper'] ),
	);
	$checks = array();
	foreach ( $pairs as list( $label, $fg, $bg ) ) {
		$ratio    = panmotors_contrast( $fg, $bg );
		$checks[] = array(
			'label' => $label,
			'ratio' => $ratio,
			'pass'  => $ratio >= 4.5,
		);
	}
	return $checks;
}

/**
 * Whether the current user may change the design settings.
 *
 * @return bool
 */
function panmotors_can_edit_design() {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}
	return current_user_can( 'edit_others_pages' ) && (bool) panmotors_option( 'design_editors', false );
}

/**
 * Hide the Design tab (fields marked pm_design) from users who may not change the design.
 * Hidden fields are not submitted, so their values stay.
 *
 * @param array $field Field.
 * @return array|false
 */
function panmotors_acf_design_only( $field ) {
	if ( ! empty( $field['pm_design'] ) && ! panmotors_can_edit_design() ) {
		return false;
	}
	return $field;
}
add_filter( 'acf/prepare_field', 'panmotors_acf_design_only' );

/**
 * Ignore posted values of fields the user cannot see (a hand-made form post), for the Design tab
 * and the admin-only Technical fields. Only form saves are checked: WP-CLI and the seed are not.
 *
 * @param mixed      $check   Null to save as usual.
 * @param mixed      $value   Value.
 * @param int|string $post_id Post ID.
 * @param array      $field   Field.
 * @return mixed
 */
function panmotors_acf_guard_update( $check, $value, $post_id, $field ) {
	if ( ! isset( $_POST['acf'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF verifies the nonce.
		return $check;
	}
	$blocked = ( ! empty( $field['pm_design'] ) && ! panmotors_can_edit_design() )
		|| ( ! empty( $field['pm_admin_only'] ) && ! current_user_can( 'manage_options' ) );
	return $blocked ? false : $check;
}
add_filter( 'acf/pre_update_value', 'panmotors_acf_guard_update', 10, 4 );

/**
 * Fill the {contrast} and {identity} messages of the Design tab.
 *
 * @param array $field Field.
 * @return array
 */
function panmotors_acf_design_messages( $field ) {
	if ( empty( $field['message'] ) || 'message' !== $field['type'] ) {
		return $field;
	}

	if ( false !== strpos( $field['message'], '{contrast}' ) ) {
		$rows = '';
		foreach ( panmotors_contrast_checks() as $check ) {
			$rows .= sprintf(
				'<li>%s %s: %s</li>',
				$check['pass'] ? '<span aria-hidden="true">✓</span>' : '<span aria-hidden="true" style="color:#b32d2e">✗</span>',
				esc_html( $check['label'] ),
				esc_html(
					$check['pass']
						/* translators: %s: contrast ratio, e.g. 4.69 */
						? sprintf( __( '%s : 1, easy to read', 'panmotors' ), number_format_i18n( $check['ratio'], 2 ) )
						/* translators: %s: contrast ratio, e.g. 3.1 */
						: sprintf( __( '%s : 1, too low (needs 4.5 : 1): small text will be hard to read', 'panmotors' ), number_format_i18n( $check['ratio'], 2 ) )
				)
			);
		}
		$field['message'] = '<p>' . esc_html__( 'Checked against the WCAG AA standard after each save.', 'panmotors' ) . '</p><ul style="margin:0">' . $rows . '</ul>';
	}

	if ( false !== strpos( $field['message'], '{identity}' ) ) {
		$link = static function ( $section, $label ) {
			return sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'customize.php?autofocus[section]=' . $section ) ), esc_html( $label ) );
		};
		$field['message'] = sprintf(
			/* translators: 1: link to the Customizer Site Identity section. 2: whether a site icon is set. */
			esc_html__( 'The main logo and the site icon (the small image in browser tabs and on phone home screens, 512 × 512px) are set in %1$s. Site icon: %2$s.', 'panmotors' ),
			$link( 'title_tagline', __( 'Appearance → Customize → Site Identity', 'panmotors' ) ),
			has_site_icon() ? esc_html__( 'set', 'panmotors' ) : esc_html__( 'not set yet', 'panmotors' )
		);
	}

	return $field;
}
add_filter( 'acf/prepare_field', 'panmotors_acf_design_messages', 20 );

/**
 * Warn on Pan Motors settings when a colour pair fails WCAG AA.
 */
function panmotors_contrast_notice() {
	$screen = get_current_screen();
	if ( ! $screen || 'toplevel_page_panmotors-settings' !== $screen->id || ! panmotors_can_edit_design() ) {
		return;
	}
	$failed = array_filter( panmotors_contrast_checks(), static fn( $c ) => ! $c['pass'] );
	if ( ! $failed ) {
		return;
	}
	$items = array_map(
		static fn( $c ) => esc_html( $c['label'] ) . ' (' . esc_html( number_format_i18n( $c['ratio'], 2 ) ) . ' : 1)',
		$failed
	);
	printf(
		'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
		esc_html__( 'Design: low colour contrast.', 'panmotors' ),
		sprintf(
			/* translators: %s: list of colour pairs with their contrast ratio. */
			esc_html__( 'These colour pairs are below the WCAG AA minimum of 4.5 : 1, so small text will be hard to read: %s. See the Design tab.', 'panmotors' ),
			implode( ', ', $items ) // Escaped above.
		)
	);
}
add_action( 'admin_notices', 'panmotors_contrast_notice' );

/**
 * Allow woff2 uploads for users who may change the design (Heading font / Body font → Custom).
 *
 * @param array $mimes Allowed types.
 * @return array
 */
function panmotors_font_mimes( $mimes ) {
	if ( panmotors_can_edit_design() ) {
		$mimes['woff2'] = 'font/woff2';
	}
	return $mimes;
}
add_filter( 'upload_mimes', 'panmotors_font_mimes' );

/**
 * PHP's file-type detection reports woff2 in several ways; accept a .woff2 whose content is a
 * woff2 file (it starts with "wOF2").
 *
 * @param array  $data     Detected ext, type and proper_filename.
 * @param string $file     Temporary file path.
 * @param string $filename Uploaded file name.
 * @return array
 */
function panmotors_font_filetype( $data, $file, $filename ) {
	if ( ! empty( $data['ext'] ) || 'woff2' !== strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) || ! panmotors_can_edit_design() ) {
		return $data;
	}
	$handle = is_readable( $file ) ? fopen( $file, 'rb' ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	$magic  = $handle ? fread( $handle, 4 ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
	if ( $handle ) {
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}
	if ( 'wOF2' === $magic ) {
		$data['ext']  = 'woff2';
		$data['type'] = 'font/woff2';
	}
	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'panmotors_font_filetype', 10, 3 );

/**
 * The logo for light backgrounds (Design tab). When there is none, the main logo is printed and
 * CSS turns it dark, as in the design.
 *
 * @param string $class CSS class for the <img>.
 * @param string $alt   Alt text ('' marks it decorative).
 * @param string $sizes Rendered width for the sizes attribute.
 */
function panmotors_logo_light_image( $class, $alt = '', $sizes = '100px' ) {
	$id = (int) panmotors_option( 'logo_light', 0 );
	if ( ! $id ) {
		panmotors_logo_image( $class, $alt, $sizes );
		return;
	}
	echo wp_get_attachment_image(
		$id,
		'medium',
		false,
		array(
			'class'   => $class . ' ' . $class . '--light',
			'alt'     => $alt,
			'sizes'   => $sizes,
			'loading' => false,
		)
	);
}

/**
 * The share image of the current page: its featured image, else the default share image.
 *
 * @return array|false { url, width, height, alt } or false.
 */
function panmotors_share_image() {
	$id = is_singular() ? (int) get_post_thumbnail_id() : 0;
	if ( ! $id ) {
		$id = (int) panmotors_option( 'share_image', 0 );
	}
	$src = $id ? wp_get_attachment_image_src( $id, 'pm-og' ) : false;
	if ( ! $src ) {
		return false;
	}
	return array(
		'url'    => $src[0],
		'width'  => $src[1],
		'height' => $src[2],
		'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
	);
}

/**
 * Print the share image in <head>. An SEO plugin prints its own Open Graph tags, so this stays
 * quiet when one is active (its default image should be set to the same file, TASKS §6).
 */
function panmotors_share_image_meta() {
	if ( defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) {
		return;
	}
	$image = panmotors_share_image();
	if ( ! $image ) {
		return;
	}
	printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image['url'] ) );
	printf( '<meta property="og:image:width" content="%d">' . "\n", (int) $image['width'] );
	printf( '<meta property="og:image:height" content="%d">' . "\n", (int) $image['height'] );
	if ( $image['alt'] ) {
		printf( '<meta property="og:image:alt" content="%s">' . "\n", esc_attr( $image['alt'] ) );
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}
add_action( 'wp_head', 'panmotors_share_image_meta', 5 );
