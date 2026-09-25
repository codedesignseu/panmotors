<?php
/**
 * CTA band: dark card on a paper band with a button, usually to the Contact page.
 * Final styling waits for the inner-page design (D10).
 *
 * Args (from blocks/cta-band/render.php): title, text, label, url. No url or label: nothing is shown.
 *
 * @package panmotors
 */

$panmotors_url   = (string) ( $args['url'] ?? '' );
$panmotors_label = (string) ( $args['label'] ?? '' );

if ( ! $panmotors_url || ! $panmotors_label ) {
	return;
}

$panmotors_title = (string) ( $args['title'] ?? '' );
$panmotors_text  = (string) ( $args['text'] ?? '' );
?>
<section class="pm-cta pm-light pm-pad"<?php echo $panmotors_title ? ' aria-labelledby="cta-title"' : ''; ?>>
	<div class="pm-cta__card">
		<div class="pm-cta__copy">
			<?php if ( $panmotors_title ) : ?>
				<h2 class="pm-cta__title" id="cta-title"><?php echo esc_html( $panmotors_title ); ?></h2>
			<?php endif; ?>
			<?php if ( $panmotors_text ) : ?>
				<p class="pm-cta__text"><?php echo esc_html( $panmotors_text ); ?></p>
			<?php endif; ?>
		</div>
		<a class="pm-pill pm-pill--solid pm-cta__link" href="<?php echo esc_url( $panmotors_url ); ?>">
			<?php echo esc_html( $panmotors_label ); ?> <span aria-hidden="true">&rarr;</span>
		</a>
	</div>
</section>
