<?php
/**
 * Hero: background video (always muted) over the poster image, one H1 (eyebrow + title), CTA.
 *
 * The poster is a real <img> (the LCP element, preloaded in <head>). The video sits on top
 * and fades in once it plays. hero-video.js starts playback after the load event, so reduced-motion
 * and no-JS visitors get the poster only. preload="none": the <img> poster covers the wait, and the
 * video download never competes with the first paint. In the editor preview there is no video.
 *
 * Args (from blocks/hero/render.php): eyebrow, title, poster (attachment ID), video_url,
 * cta_label, cta_link, preview.
 *
 * @package panmotors
 */

$panmotors_eyebrow   = (string) ( $args['eyebrow'] ?? '' );
$panmotors_title     = (string) ( $args['title'] ?? '' );
$panmotors_poster_id = (int) ( $args['poster'] ?? 0 );
$panmotors_video_url = empty( $args['preview'] ) ? (string) ( $args['video_url'] ?? '' ) : '';
$panmotors_cta_label = (string) ( $args['cta_label'] ?? '' );
$panmotors_cta_link  = (string) ( $args['cta_link'] ?? '' );

if ( ! $panmotors_title && ! $panmotors_eyebrow ) {
	return;
}
?>
<section id="top" class="pm-hero" aria-labelledby="hero-title" <?php echo $panmotors_video_url ? 'data-hero-video' : ''; ?>>
	<div class="pm-hero__media" aria-hidden="true">
		<?php
		if ( $panmotors_poster_id ) {
			echo wp_get_attachment_image(
				$panmotors_poster_id,
				'pm-hero',
				false,
				array(
					'class'         => 'pm-hero__poster',
					'alt'           => '',
					'sizes'         => '100vw',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
				)
			);
		}

		if ( $panmotors_video_url ) :
			?>
			<video class="pm-hero__video" src="<?php echo esc_url( $panmotors_video_url ); ?>" muted loop playsinline preload="none" tabindex="-1"></video>
		<?php endif; ?>
	</div>
	<div class="pm-hero__shade" aria-hidden="true"></div>

	<div class="pm-hero__copy">
		<h1 class="pm-hero__title" id="hero-title">
			<?php if ( $panmotors_eyebrow ) : ?>
				<span class="pm-eyebrow pm-hero__eyebrow" data-hero-in="1"><?php echo esc_html( $panmotors_eyebrow ); ?></span>
			<?php endif; ?>
			<?php if ( $panmotors_title ) : ?>
				<span class="pm-hero__line" data-hero-in="2"><?php echo nl2br( esc_html( trim( $panmotors_title ) ), false ); ?></span>
			<?php endif; ?>
		</h1>

		<?php if ( $panmotors_cta_label && $panmotors_cta_link ) : ?>
			<div class="pm-hero__actions" data-hero-in="3">
				<a class="pm-pill pm-pill--solid pm-hero__cta" href="<?php echo esc_url( $panmotors_cta_link ); ?>">
					<?php echo esc_html( $panmotors_cta_label ); ?> <span class="pm-hero__cta-arrow" aria-hidden="true">&darr;</span>
				</a>
			</div>
		<?php endif; ?>
	</div>
</section>
