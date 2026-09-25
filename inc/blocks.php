<?php
/**
 * Gutenberg + ACF Blocks (D11, docs/blocks.md).
 *
 * Every page is a list of blocks. Sections are ACF Blocks in blocks/<name>/ (block.json +
 * render.php); render.php gathers the data and passes it to the section view in template-parts/.
 * This file registers them and keeps the editor inside the design: allowed blocks, editor
 * settings, editor styles and script, and the front-end guards that keep the HTML unchanged.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

/**
 * Core blocks the client may use for plain text on inner pages.
 */
const PANMOTORS_CORE_BLOCKS = array(
	'core/block',
	'core/heading',
	'core/paragraph',
	'core/list',
	'core/list-item',
	'core/image',
	'core/quote',
	'core/buttons',
	'core/button',
);

/**
 * Register every block in blocks/.
 */
function panmotors_register_blocks() {
	if ( ! function_exists( 'acf_register_block_type' ) ) {
		return;
	}
	foreach ( glob( PANMOTORS_DIR . '/blocks/*/block.json' ) as $json ) {
		register_block_type( dirname( $json ) );
	}
}
add_action( 'init', 'panmotors_register_blocks', 5 );

/**
 * "Pan Motors" block category, first in the inserter.
 *
 * @param array $categories Categories.
 * @return array
 */
function panmotors_block_category( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'panmotors',
			'title' => __( 'Pan Motors', 'panmotors' ),
			'icon'  => null,
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'panmotors_block_category' );

/**
 * Inserter previews use the real content: the data of the same block on the homepage (or in a
 * synced pattern), so images and cars show as they do on the site. Admin only.
 *
 * @param array $metadata block.json metadata.
 * @return array
 */
function panmotors_block_examples( $metadata ) {
	if ( ! is_admin() || 0 !== strpos( $metadata['name'] ?? '', 'pm/' ) ) {
		return $metadata;
	}
	$found = panmotors_find_block( (int) get_option( 'page_on_front' ), $metadata['name'] );
	if ( $found && ! empty( $found['attrs']['data'] ) ) {
		$metadata['example'] = array(
			'viewportWidth' => 1440,
			'attributes'    => array(
				'mode' => 'preview',
				'data' => $found['attrs']['data'],
			),
		);
	}
	return $metadata;
}
add_filter( 'block_type_metadata', 'panmotors_block_examples' );

/**
 * Find the first block of a type in a post, looking inside synced patterns and inner blocks.
 * Hidden blocks (Hide on the block toolbar) are skipped.
 *
 * @param int    $post_id Post ID.
 * @param string $name    Block name, e.g. 'pm/hero'.
 * @return array|null Parsed block.
 */
function panmotors_find_block( $post_id, $name ) {
	$post = $post_id ? get_post( $post_id ) : null;
	if ( ! $post || ! has_blocks( $post->post_content ) ) {
		return null;
	}
	$walk = static function ( $blocks, $depth ) use ( &$walk, $name ) {
		foreach ( $blocks as $block ) {
			if ( false === ( $block['attrs']['metadata']['blockVisibility'] ?? null ) ) {
				continue;
			}
			if ( $name === $block['blockName'] ) {
				return $block;
			}
			if ( 'core/block' === $block['blockName'] && ! empty( $block['attrs']['ref'] ) && $depth < 3 ) {
				$ref = get_post( (int) $block['attrs']['ref'] );
				if ( $ref && 'publish' === $ref->post_status ) {
					$hit = $walk( parse_blocks( $ref->post_content ), $depth + 1 );
					if ( $hit ) {
						return $hit;
					}
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$hit = $walk( $block['innerBlocks'], $depth );
				if ( $hit ) {
					return $hit;
				}
			}
		}
		return null;
	};
	return $walk( parse_blocks( $post->post_content ), 0 );
}

/**
 * Read one field of a parsed ACF block outside its render (for <head>: preload, description).
 *
 * @param array|null $block Parsed block from panmotors_find_block().
 * @param string     $name  Field name.
 * @return mixed
 */
function panmotors_block_field( $block, $name ) {
	if ( ! $block || ! function_exists( 'acf_prepare_block' ) ) {
		return null;
	}
	$prepared = acf_prepare_block( $block['attrs'] );
	if ( ! $prepared ) {
		return null;
	}
	acf_setup_meta( $prepared['data'], $prepared['id'], true );
	$value = get_field( $name );
	acf_reset_meta( $prepared['id'] );
	return $value;
}

/**
 * Render a section view for a block. Called from each blocks/<name>/render.php.
 *
 * Front end: prints the view and loads its JS module only when it printed something (the
 * behaviour of the old section renderer). Editor preview: no module, and an empty section shows
 * a placeholder so the client can still select the block.
 *
 * @param string $part       Template part, e.g. 'template-parts/sections/values'.
 * @param string $module     JS module name, or ''.
 * @param array  $args       View arguments.
 * @param bool   $is_preview Editor preview.
 * @param string $empty      Placeholder text for an empty block in the editor.
 */
function panmotors_render_block( $part, $module, $args, $is_preview, $empty = '' ) {
	$args['preview'] = (bool) $is_preview;
	ob_start();
	get_template_part( $part, null, $args );
	$html = trim( (string) ob_get_clean() );

	if ( '' === $html ) {
		if ( $is_preview ) {
			printf( '<div class="pm-block-empty">%s</div>', esc_html( $empty ? $empty : __( 'Nothing to show yet. Fill in this block in the sidebar.', 'panmotors' ) ) );
		}
		return;
	}

	echo $html . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the template part.

	if ( $module && ! $is_preview ) {
		panmotors_use_module( $module );
	}
}

/**
 * A hidden block (Hide on the block toolbar, WordPress 6.9+) is not rendered at all, so its
 * JS module is not loaded either. Core would print nothing for it anyway.
 *
 * @param string|null $pre_render Short-circuit value.
 * @param array       $block      Parsed block.
 * @return string|null
 */
function panmotors_skip_hidden_block( $pre_render, $block ) {
	if ( false === ( $block['attrs']['metadata']['blockVisibility'] ?? null ) ) {
		return '';
	}
	return $pre_render;
}
add_filter( 'pre_render_block', 'panmotors_skip_hidden_block', 10, 2 );

/**
 * Allowed blocks: every pm/* block and the core text blocks. Nothing else from core.
 *
 * @param bool|string[]           $allowed Allowed blocks.
 * @param WP_Block_Editor_Context $context Editor context.
 * @return bool|string[]
 */
function panmotors_allowed_blocks( $allowed, $context ) {
	if ( ! empty( $context->post ) && ! in_array( $context->post->post_type, array( 'page', 'wp_block' ), true ) ) {
		return $allowed;
	}
	$pm = array_values(
		array_filter(
			array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() ),
			static fn( $name ) => 0 === strpos( $name, 'pm/' )
		)
	);
	return array_merge( $pm, PANMOTORS_CORE_BLOCKS );
}
add_filter( 'allowed_block_types_all', 'panmotors_allowed_blocks', 10, 2 );

/**
 * Editor settings: no Openverse, no code editor for the client, no remote or core patterns.
 *
 * @param array $settings Editor settings.
 * @return array
 */
function panmotors_editor_settings( $settings ) {
	$settings['enableOpenverseMediaCategory'] = false;
	if ( ! current_user_can( 'manage_options' ) ) {
		$settings['codeEditingEnabled'] = false;
	}
	return $settings;
}
add_filter( 'block_editor_settings_all', 'panmotors_editor_settings' );
add_filter( 'should_load_remote_block_patterns', '__return_false' );
remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );

/**
 * No core patterns, no block templates (a classic theme with page content in blocks).
 */
function panmotors_block_supports() {
	remove_theme_support( 'core-block-patterns' );
	remove_theme_support( 'block-templates' );
}
add_action( 'after_setup_theme', 'panmotors_block_supports', 20 );

/**
 * Pattern category for the theme's patterns.
 */
function panmotors_pattern_category() {
	register_block_pattern_category( 'panmotors', array( 'label' => __( 'Pan Motors', 'panmotors' ) ) );
}
add_action( 'init', 'panmotors_pattern_category' );

/**
 * Front-end styles, fonts and the editor stylesheet inside the editor canvas (iframe).
 * enqueue_block_assets also runs on the front end, where main.css is already enqueued.
 */
function panmotors_editor_canvas_assets() {
	if ( ! is_admin() ) {
		return;
	}
	$css = 'assets/css/main.css';
	wp_enqueue_style( 'pm-main', PANMOTORS_URI . '/' . $css, array(), panmotors_asset_version( $css ) );
	$editor = 'assets/css/editor.css';
	wp_enqueue_style( 'pm-editor', PANMOTORS_URI . '/' . $editor, array( 'pm-main' ), panmotors_asset_version( $editor ) );
}
add_action( 'enqueue_block_assets', 'panmotors_editor_canvas_assets' );

/**
 * Editor script: limits text formats and block styles to what the design has.
 */
function panmotors_editor_script() {
	$js = 'assets/js/editor.js';
	wp_enqueue_script( 'pm-editor', PANMOTORS_URI . '/' . $js, array( 'wp-blocks', 'wp-rich-text', 'wp-data', 'wp-dom-ready' ), panmotors_asset_version( $js ), true );
}
add_action( 'enqueue_block_editor_assets', 'panmotors_editor_script' );

/**
 * theme.json makes WordPress print its global styles (preset variables, element and layout
 * rules). The design defines everything in main.css, so none of that reaches the front end.
 */
function panmotors_front_block_styles() {
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
	if ( ! panmotors_has_core_blocks() ) {
		wp_dequeue_style( 'wp-block-library' );
	}
}
add_action( 'wp_enqueue_scripts', 'panmotors_front_block_styles', 100 );
remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );

/**
 * Whether the current page uses core blocks other than synced pattern references.
 *
 * @return bool
 */
function panmotors_has_core_blocks() {
	if ( ! is_singular() ) {
		return false;
	}
	$content = (string) get_post_field( 'post_content', get_queried_object_id() );
	return (bool) preg_match( '/<!-- wp:(?!block |pm\/)[a-z]/', $content );
}

/**
 * Section text is printed exactly as the client typed it, as before the blocks: no curly
 * quotes, smilies or other the_content rewriting on pages built from pm/* blocks.
 */
function panmotors_content_filters() {
	if ( ! is_singular() || false === strpos( (string) get_post_field( 'post_content', get_queried_object_id() ), '<!-- wp:pm/' ) ) {
		return;
	}
	remove_filter( 'the_content', 'wptexturize' );
	remove_filter( 'the_content', 'convert_smilies', 20 );
	remove_filter( 'the_content', 'capital_P_dangit', 11 );
}
add_action( 'template_redirect', 'panmotors_content_filters' );
