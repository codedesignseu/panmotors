<?php
/**
 * Block pm/visit (Visit, D12). Hours, address, phones, email and map link from Pan Motors settings;
 * the small red lines and the button text from the block.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/sections/visit',
	'',
	array(
		'hours_label' => get_field( 'visit_hours_label' ),
		'find_label'  => get_field( 'visit_find_label' ),
		'directions'  => get_field( 'visit_directions_label' ),
	),
	$is_preview,
	__( 'Visit: fill in the opening hours and address in Pan Motors settings.', 'panmotors' )
);
