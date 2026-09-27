<?php
/**
 * Block pm/contact-details (Contact rows, D12). Details from Pan Motors settings; labels and
 * action texts from the block.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

$panmotors_texts = array();
foreach ( array( 'call', 'email', 'find', 'follow' ) as $panmotors_row ) {
	$panmotors_texts[ $panmotors_row ] = array(
		'label'  => get_field( 'cd_' . $panmotors_row . '_label' ),
		'action' => get_field( 'cd_' . $panmotors_row . '_action' ),
	);
}

panmotors_render_block(
	'template-parts/sections/contact-details',
	'',
	array( 'texts' => $panmotors_texts ),
	$is_preview,
	__( 'Contact rows: fill in the phone, email and address in Pan Motors settings.', 'panmotors' )
);
