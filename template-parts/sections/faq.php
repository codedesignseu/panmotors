<?php
/**
 * Questions (theme-map 9.4): native details/summary rows styled like the opening hours, so the
 * answers are in the HTML and work without JS. The FAQPage JSON-LD reads the same block.
 *
 * Args (from blocks/faq/render.php):
 * - title (string)  Heading.
 * - faqs  (array[]) Rows: question, answer.
 *
 * @package panmotors
 */

$panmotors_faqs = array_filter(
	(array) ( $args['faqs'] ?? array() ),
	static fn( $row ) => '' !== trim( (string) ( $row['question'] ?? '' ) ) && '' !== trim( (string) ( $row['answer'] ?? '' ) )
);

if ( ! $panmotors_faqs ) {
	return;
}

$panmotors_title = (string) ( $args['title'] ?? '' );
?>
<section class="pm-faq pm-light pm-pad pm-pad-y"<?php echo $panmotors_title ? ' aria-labelledby="faq-title"' : ''; ?>>
	<?php if ( $panmotors_title ) : ?>
		<h2 class="pm-title pm-faq__title" id="faq-title" data-rise><?php echo esc_html( $panmotors_title ); ?></h2>
	<?php endif; ?>
	<div class="pm-faq__list">
		<?php foreach ( $panmotors_faqs as $panmotors_faq ) : ?>
			<details class="pm-faq__item">
				<summary class="pm-faq__question"><?php echo esc_html( $panmotors_faq['question'] ); ?></summary>
				<p class="pm-faq__answer"><?php echo esc_html( $panmotors_faq['answer'] ); ?></p>
			</details>
		<?php endforeach; ?>
	</div>
</section>
