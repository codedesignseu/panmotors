<?php
/**
 * Block pm/contact-form (Contact form and map, D12). The form shortcode falls back to the one in
 * Pan Motors settings; the map query, map link and hours come from the settings too.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_form = array();
foreach ( array( 'name', 'email', 'subject', 'message' ) as $panmotors_key ) {
	$panmotors_form[ $panmotors_key ] = array(
		'label' => get_field( 'cf_label_' . $panmotors_key ),
		'hint'  => get_field( 'cf_hint_' . $panmotors_key ),
	);
}
$panmotors_shortcode = trim( (string) get_field( 'cf_shortcode' ) );

panmotors_render_block(
	'template-parts/sections/contact-form',
	'contact-map',
	array(
		'title'      => get_field( 'cf_title' ),
		'shortcode'  => $panmotors_shortcode ? $panmotors_shortcode : trim( (string) panmotors_option( 'enquire_form_shortcode', '' ) ),
		'form'       => $panmotors_form,
		'button'     => get_field( 'cf_button' ),
		'map_note'   => get_field( 'cf_map_note' ),
		'map_button' => get_field( 'cf_map_button' ),
		'map_link'   => get_field( 'cf_map_link' ),
		// A block saved before this field existed has no value: the field default (with the page).
		'map_auto'   => false !== get_field( 'cf_map_autoload' ) && '0' !== (string) get_field( 'cf_map_autoload' ),
		'hours'      => get_field( 'cf_hours_label' ),
	),
	$is_preview
);
