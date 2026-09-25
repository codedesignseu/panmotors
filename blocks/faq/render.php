<?php
/**
 * Block pm/faq (Questions). Data from the block fields, markup in the section view.
 *
 * @var bool $is_preview Editor preview.
 * @var int  $post_id    Page being rendered.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

panmotors_render_block(
	'template-parts/sections/faq',
	'',
	array(
		'title' => get_field( 'faq_title' ),
		'faqs'  => panmotors_rows( 'faqs' ),
	),
	$is_preview,
	__( 'Questions: add questions in the sidebar.', 'panmotors' )
);
