<?php
/**
 * Photo call to action (inner-pages §2.4): a rounded card with a darkened black-and-white photo, an
 * optional small red line, a large heading and one or two buttons, on a light section.
 *
 * Args (from blocks/cta-image/render.php):
 * - eyebrow (string)  Small red line.
 * - title   (string)  Heading; new lines start new lines.
 * - image   (int)     Background photo.
 * - colour  (bool)    Photo in colour (else black and white).
 * - bright  (int)     Photo brightness in percent, 40–70 (default 55).
 * - size    (string)  'tall' (About) or 'standard' (Showroom: less padding, lighter gradient).
 * - buttons (array[]) [label, url] pairs: the first is the solid button, the second outlined.
 *
 * @package panmotors
 */

$panmotors_title   = trim( (string) ( $args['title'] ?? '' ) );
$panmotors_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$panmotors_image   = (int) ( $args['image'] ?? 0 );
$panmotors_buttons = array_values(
	array_filter(
		(array) ( $args['buttons'] ?? array() ),
		static fn( $b ) => '' !== trim( (string) ( $b[0] ?? '' ) ) && '' !== (string) ( $b[1] ?? '' )
	)
);

if ( ! $panmotors_title ) {
	return;
}

$panmotors_bright = is_numeric( $args['bright'] ?? null ) ? max( 40, min( 70, (int) $args['bright'] ) ) : 55;
$panmotors_class  = 'pm-cta-image__card';
$panmotors_class .= ! empty( $args['colour'] ) ? ' pm-cta-image__card--colour' : '';
$panmotors_class .= 'standard' === ( $args['size'] ?? '' ) ? ' pm-cta-image__card--standard' : '';
?>
<section class="pm-cta-image pm-light pm-pad" aria-labelledby="cta-image-title">
	<div class="<?php echo esc_attr( $panmotors_class ); ?>"<?php echo 55 !== $panmotors_bright ? ' style="--pm-brightness: ' . esc_attr( (string) ( $panmotors_bright / 100 ) ) . '"' : ''; ?> data-rise data-zoom>
		<?php if ( $panmotors_image ) : ?>
			<div class="pm-cta-image__media" aria-hidden="true">
				<?php
				echo wp_get_attachment_image(
					$panmotors_image,
					'pm-hero',
					false,
					array(
						'alt'     => '',
						'sizes'   => 'calc(100vw - 80px)',
						'loading' => 'lazy',
					)
				);
				?>
			</div>
		<?php endif; ?>
		<div class="pm-cta-image__shade" aria-hidden="true"></div>

		<?php if ( $panmotors_eyebrow ) : ?>
			<p class="pm-eyebrow pm-cta-image__eyebrow"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
		<?php endif; ?>
		<h2 class="pm-cta-image__title" id="cta-image-title"><?php echo nl2br( esc_html( $panmotors_title ), false ); ?></h2>

		<?php if ( $panmotors_buttons ) : ?>
			<div class="pm-cta-image__actions">
				<?php foreach ( $panmotors_buttons as $panmotors_i => list( $panmotors_label, $panmotors_url ) ) : ?>
					<?php if ( 0 === $panmotors_i ) : ?>
						<a class="pm-pill pm-pill--solid" href="<?php echo esc_url( $panmotors_url ); ?>"><?php echo esc_html( trim( $panmotors_label ) ); ?> <span class="pm-pill__arrow" aria-hidden="true">&rarr;</span></a>
					<?php else : ?>
						<a class="pm-pill pm-cta-image__outline" href="<?php echo esc_url( $panmotors_url ); ?>"><?php echo esc_html( trim( $panmotors_label ) ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
