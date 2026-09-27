<?php
/**
 * Story (About, inner-pages §5): heading and paragraphs left, a 4:5 photo right.
 *
 * Args (from blocks/story/render.php):
 * - eyebrow (string) Small red line.
 * - title   (string) Heading; new lines start new lines.
 * - lead    (string) First paragraph (defaults to the business description).
 * - text    (string) More paragraphs (rich text).
 * - image   (int)    Portrait photo.
 *
 * @package panmotors
 */

$panmotors_title   = trim( (string) ( $args['title'] ?? '' ) );
$panmotors_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$panmotors_lead    = trim( (string) ( $args['lead'] ?? '' ) );
$panmotors_text    = trim( (string) ( $args['text'] ?? '' ) );
$panmotors_image   = (int) ( $args['image'] ?? 0 );

if ( ! $panmotors_title && ! $panmotors_image ) {
	return;
}
?>
<section class="pm-story pm-pad"<?php echo $panmotors_title ? ' aria-labelledby="story-title"' : ''; ?>>
	<div class="pm-story__grid">
		<div class="pm-story__copy" data-rise>
			<?php if ( $panmotors_eyebrow ) : ?>
				<p class="pm-eyebrow pm-story__eyebrow"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
			<?php endif; ?>
			<?php if ( $panmotors_title ) : ?>
				<h2 class="pm-story__title" id="story-title"><?php echo nl2br( esc_html( $panmotors_title ), false ); ?></h2>
			<?php endif; ?>
			<?php if ( $panmotors_lead || $panmotors_text ) : ?>
				<div class="pm-story__text">
					<?php if ( $panmotors_lead ) : ?>
						<p><?php echo esc_html( $panmotors_lead ); ?></p>
					<?php endif; ?>
					<?php echo wp_kses_post( $panmotors_text ); ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $panmotors_image ) : ?>
			<div class="pm-media pm-story__media" data-rise-l data-zoom>
				<?php
				echo wp_get_attachment_image(
					$panmotors_image,
					'pm-portrait',
					false,
					array(
						'sizes'   => '(max-width: 808px) calc(100vw - 40px), 50vw',
						'loading' => 'lazy',
					)
				);
				?>
			</div>
		<?php endif; ?>
	</div>
</section>
