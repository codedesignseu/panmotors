<?php
/**
 * Block pm/cta-band (Call to action). Data from the block fields, markup in the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/components/cta-band',
	'',
	array(
		'title' => get_field( 'cta_title' ),
		'text'  => get_field( 'cta_text' ),
		'label' => get_field( 'cta_label' ),
		'url'   => get_field( 'cta_link' ),
	),
	$is_preview,
	__( 'Call to action: choose the page the button goes to.', 'panmotors' )
);
