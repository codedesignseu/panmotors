<?php
/**
 * CTA band (inner-pages §2.4, _design/v2/cars.html): a paper card on the dark page, heading left,
 * an ink button right, usually to the Contact page. Optional short text under the heading.
 *
 * Args (from blocks/cta-band/render.php): title (new lines kept), text, label, url. No url or
 * label: nothing is shown.
 *
 * @package panmotors
 */

$panmotors_url   = (string) ( $args['url'] ?? '' );
$panmotors_label = trim( (string) ( $args['label'] ?? '' ) );

if ( ! $panmotors_url || ! $panmotors_label ) {
	return;
}

$panmotors_title = trim( (string) ( $args['title'] ?? '' ) );
$panmotors_text  = trim( (string) ( $args['text'] ?? '' ) );
?>
<section class="pm-cta pm-pad"<?php echo $panmotors_title ? ' aria-labelledby="cta-title"' : ''; ?>>
	<div class="pm-cta__card pm-light" data-rise>
		<div class="pm-cta__copy">
			<?php if ( $panmotors_title ) : ?>
				<h2 class="pm-cta__title" id="cta-title"><?php echo nl2br( esc_html( $panmotors_title ), false ); ?></h2>
			<?php endif; ?>
			<?php if ( $panmotors_text ) : ?>
				<p class="pm-cta__text"><?php echo esc_html( $panmotors_text ); ?></p>
			<?php endif; ?>
		</div>
		<a class="pm-cta__link" href="<?php echo esc_url( $panmotors_url ); ?>"><?php echo esc_html( $panmotors_label ); ?> <span class="pm-pill__arrow" aria-hidden="true">&rarr;</span></a>
	</div>
</section>
