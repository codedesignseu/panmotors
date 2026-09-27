<?php
/**
 * Block pm/services (What We Do, D12). Photo tiles in a row.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/sections/services',
	'',
	array(
		'title'    => get_field( 'services_title' ),
		'services' => panmotors_rows( 'services' ),
	),
	$is_preview,
	__( 'What We Do: add services in the sidebar.', 'panmotors' )
);
