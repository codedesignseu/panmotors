<?php
/**
 * JSON-LD structured data: one @graph (AutoDealer, WebSite, WebPage, FAQPage)
 * built from the ACF options page. See docs/theme-map.md section 9.3.
 *
 * Filled in during section 5.
 *
 * @package panmotors
 */

defined( 'ABSPATH' ) || exit;

/**
 * The questions and answers of the pm/faq blocks on a page, exactly as the page prints them
 * (template-parts/sections/faq.php): rows with both a question and an answer, trimmed, in order.
 * Hidden blocks (Hide on the block toolbar) are left out. For the FAQPage node of the @graph
 * (TASKS §5); nothing prints JSON-LD yet.
 *
 * @param int $post_id Page ID. Defaults to the current page.
 * @return array[] Items: question, answer.
 */
function panmotors_faq_items( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	$content = $post_id ? (string) get_post_field( 'post_content', $post_id ) : '';
	if ( false === strpos( $content, '<!-- wp:pm/faq' ) ) {
		return array();
	}

	$items = array();
	$walk  = static function ( $blocks ) use ( &$walk, &$items ) {
		foreach ( $blocks as $block ) {
			if ( false === ( $block['attrs']['metadata']['blockVisibility'] ?? null ) ) {
				continue;
			}
			if ( 'pm/faq' === $block['blockName'] ) {
				$count = (int) panmotors_block_field( $block, 'faqs' );
				for ( $i = 0; $i < $count; $i++ ) {
					$question = trim( (string) panmotors_block_field( $block, "faqs_{$i}_question" ) );
					$answer   = trim( (string) panmotors_block_field( $block, "faqs_{$i}_answer" ) );
					if ( '' !== $question && '' !== $answer ) {
						$items[] = array(
							'question' => $question,
							'answer'   => $answer,
						);
					}
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$walk( $block['innerBlocks'] );
			}
		}
	};
	$walk( parse_blocks( $content ) );
	return $items;
}
