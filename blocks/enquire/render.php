<?php
/**
 * Block pm/enquire (Come And See). Data from the block fields, markup in the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_form = array();
foreach ( array( 'name', 'email', 'phone', 'message' ) as $panmotors_key ) {
	$panmotors_form[ $panmotors_key ] = array(
		'label' => get_field( 'form_label_' . $panmotors_key ),
		'hint'  => (string) get_field( 'form_hint_' . $panmotors_key ),
	);
}

panmotors_render_block(
	'template-parts/sections/enquire',
	'',
	array(
		'title'  => get_field( 'enquire_title' ),
		'intro'  => get_field( 'enquire_intro' ),
		'form'   => $panmotors_form,
		'button' => get_field( 'form_button' ),
	),
	$is_preview
);
