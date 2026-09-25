<?php
/**
 * Cars: a non-public data store for the showcase cars (D11, docs/blocks.md).
 *
 * No URLs, no single or archive pages, not in search or sitemaps (D1 stands). The client adds
 * a car once under Cars; pm/featured-cars and pm/latest-cars read it from here.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the pm_car post type.
 */
function panmotors_register_cars() {
	register_post_type(
		'pm_car',
		array(
			'labels'              => array(
				'name'               => __( 'Cars', 'panmotors' ),
				'singular_name'      => __( 'Car', 'panmotors' ),
				'menu_name'          => __( 'Cars', 'panmotors' ),
				'add_new'            => __( 'Add car', 'panmotors' ),
				'add_new_item'       => __( 'Add car', 'panmotors' ),
				'edit_item'          => __( 'Edit car', 'panmotors' ),
				'new_item'           => __( 'New car', 'panmotors' ),
				'search_items'       => __( 'Search cars', 'panmotors' ),
				'not_found'          => __( 'No cars yet.', 'panmotors' ),
				'not_found_in_trash' => __( 'No cars in the bin.', 'panmotors' ),
				'all_items'          => __( 'All cars', 'panmotors' ),
			),
			'description'         => __( 'Showcase cars for Featured Cars and Latest Cars. No prices, no car pages.', 'panmotors' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => true, // The block editor's relationship field lists cars through REST.
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_position'       => 21,
			'menu_icon'           => 'dashicons-car',
			'capability_type'     => 'page', // Editors manage cars like pages.
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'page-attributes' ),
		)
	);
}
add_action( 'init', 'panmotors_register_cars' );

/**
 * Cars edit in the classic screen: the fields are the whole form.
 *
 * @param bool   $use_block_editor Whether to use the block editor.
 * @param string $post_type        Post type.
 * @return bool
 */
function panmotors_cars_classic_editor( $use_block_editor, $post_type ) {
	return 'pm_car' === $post_type ? false : $use_block_editor;
}
add_filter( 'use_block_editor_for_post_type', 'panmotors_cars_classic_editor', 10, 2 );

/**
 * Hide the title box: the name is set from marque and model.
 */
function panmotors_cars_no_title() {
	remove_post_type_support( 'pm_car', 'title' );
}
add_action( 'load-post.php', 'panmotors_cars_no_title' );
add_action( 'load-post-new.php', 'panmotors_cars_no_title' );

/**
 * Keep the car's title in step with its marque and model, so the Cars list reads well.
 *
 * @param int|string $post_id Post ID.
 */
function panmotors_cars_title( $post_id ) {
	if ( ! is_numeric( $post_id ) || 'pm_car' !== get_post_type( (int) $post_id ) ) {
		return;
	}
	$title = trim( get_field( 'marque', (int) $post_id ) . ' ' . get_field( 'model_name', (int) $post_id ) );
	if ( $title && get_the_title( (int) $post_id ) !== $title ) {
		remove_action( 'acf/save_post', 'panmotors_cars_title', 20 );
		wp_update_post(
			array(
				'ID'         => (int) $post_id,
				'post_title' => $title,
				'post_name'  => sanitize_title( $title ),
			)
		);
		add_action( 'acf/save_post', 'panmotors_cars_title', 20 );
	}
}
add_action( 'acf/save_post', 'panmotors_cars_title', 20 );

/**
 * Cars list columns: photo, car, Featured, order, date.
 *
 * @param array $columns Columns.
 * @return array
 */
function panmotors_cars_columns( $columns ) {
	return array(
		'cb'          => $columns['cb'],
		'pm_photo'    => __( 'Photo', 'panmotors' ),
		'title'       => __( 'Car', 'panmotors' ),
		'pm_featured' => __( 'Featured', 'panmotors' ),
		'pm_order'    => __( 'Order', 'panmotors' ),
		'date'        => $columns['date'],
	);
}
add_filter( 'manage_pm_car_posts_columns', 'panmotors_cars_columns' );

/**
 * Print the custom column values.
 *
 * @param string $column  Column key.
 * @param int    $post_id Car ID.
 */
function panmotors_cars_column( $column, $post_id ) {
	if ( 'pm_photo' === $column ) {
		$image = (int) get_field( 'image', $post_id );
		if ( $image ) {
			echo wp_get_attachment_image( $image, array( 96, 54 ), false, array( 'class' => 'pm-admin-thumb' ) );
		}
	} elseif ( 'pm_featured' === $column ) {
		echo get_field( 'featured', $post_id ) ? '<span class="pm-admin-featured">' . esc_html__( 'Featured', 'panmotors' ) . '</span>' : '<span aria-hidden="true">—</span>';
	} elseif ( 'pm_order' === $column ) {
		// The order only applies to the Featured Cars row.
		echo get_field( 'featured', $post_id ) ? esc_html( (string) get_post_field( 'menu_order', $post_id ) ) : '<span aria-hidden="true">—</span>';
	}
}
add_action( 'manage_pm_car_posts_custom_column', 'panmotors_cars_column', 10, 2 );

/**
 * Column widths and the Featured badge.
 */
function panmotors_cars_admin_css() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-pm_car' !== $screen->id ) {
		return;
	}
	echo '<style>.column-pm_photo{width:112px}.column-pm_featured,.column-pm_order{width:110px}.pm-admin-thumb{display:block;width:96px;height:54px;object-fit:cover;border-radius:4px}.pm-admin-featured{display:inline-block;padding:2px 8px;border-radius:10px;background:#ec3013;color:#fff;font-size:11px;letter-spacing:.04em}</style>';
}
add_action( 'admin_head', 'panmotors_cars_admin_css' );

/**
 * Cars list shows the newest first, like Latest Cars, unless the client sorts by a column.
 *
 * @param WP_Query $query Query.
 */
function panmotors_cars_admin_order( $query ) {
	if ( is_admin() && $query->is_main_query() && 'pm_car' === $query->get( 'post_type' ) && ! isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$query->set( 'orderby', array( 'date' => 'DESC' ) );
	}
}
add_action( 'pre_get_posts', 'panmotors_cars_admin_order' );

/**
 * Never in the XML sitemap.
 *
 * @param array $post_types Post types.
 * @return array
 */
function panmotors_cars_sitemap( $post_types ) {
	unset( $post_types['pm_car'] );
	return $post_types;
}
add_filter( 'wp_sitemaps_post_types', 'panmotors_cars_sitemap' );

/**
 * Cars as rows for the section views: image, marque, model_name, ref_no, spec, note, link, caption, place.
 *
 * @param array $args {
 *     @type string $mode  'featured' (marked Featured, by order), 'pick' (the given IDs, in that order) or 'latest' (newest first).
 *     @type int[]  $ids   Car IDs for 'pick'.
 *     @type int    $limit Max cars.
 * }
 * @return array[]
 */
function panmotors_cars( $args ) {
	$limit = max( 1, (int) ( $args['limit'] ?? 12 ) );
	$query = array(
		'post_type'              => 'pm_car',
		'post_status'            => 'publish',
		'posts_per_page'         => $limit,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
	);

	switch ( $args['mode'] ?? 'latest' ) {
		case 'pick':
			$ids = array_filter( array_map( 'intval', (array) ( $args['ids'] ?? array() ) ) );
			if ( ! $ids ) {
				return array();
			}
			$query['post__in'] = $ids;
			$query['orderby']  = 'post__in';
			break;
		case 'featured':
			$query['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => 'featured',
					'value' => '1',
				),
			);
			$query['orderby']    = array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			);
			break;
		default:
			$query['orderby'] = array( 'date' => 'DESC' );
	}

	$rows = array();
	foreach ( get_posts( $query ) as $car ) {
		$row = array( 'id' => $car->ID );
		foreach ( array( 'image', 'marque', 'model_name', 'ref_no', 'spec', 'note', 'link', 'caption', 'place' ) as $name ) {
			$row[ $name ] = get_field( $name, $car->ID );
		}
		$rows[] = $row;
	}
	return $rows;
}
