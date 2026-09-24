<?php
/**
 * CTA band: dark card on a paper band, linking to the Contact page. Not used on Contact itself.
 *
 * Args (all optional):
 * - title (string) Heading. Default "Come and see".
 * - text  (string) One line under the heading.
 * - label (string) Button label. Default "Contact us".
 * - url   (string) Button link. Default the Contact page.
 *
 * @package panmotors
 */

$panmotors_url = $args['url'] ?? panmotors_page_url( 'contact' );

if ( ! $panmotors_url ) {
	return;
}

$panmotors_title = $args['title'] ?? __( 'Come and see', 'panmotors' );
$panmotors_text  = $args['text'] ?? __( 'Call, write, or walk in during showroom hours. Someone from the family will answer.', 'panmotors' );
$panmotors_label = $args['label'] ?? __( 'Contact us', 'panmotors' );
?>
<section class="pm-cta pm-light pm-pad" aria-labelledby="cta-title">
	<div class="pm-cta__card">
		<div class="pm-cta__copy">
			<h2 class="pm-cta__title" id="cta-title"><?php echo esc_html( $panmotors_title ); ?></h2>
			<?php if ( $panmotors_text ) : ?>
				<p class="pm-cta__text"><?php echo esc_html( $panmotors_text ); ?></p>
			<?php endif; ?>
		</div>
		<a class="pm-pill pm-pill--solid pm-cta__link" href="<?php echo esc_url( $panmotors_url ); ?>">
			<?php echo esc_html( $panmotors_label ); ?> <span aria-hidden="true">&rarr;</span>
		</a>
	</div>
</section>
