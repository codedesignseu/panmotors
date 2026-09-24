<?php
/**
 * Hero: background video (always muted) over the poster image, one H1 (eyebrow + title), CTA.
 *
 * The poster is a real <img> (the LCP element, preloaded in <head>). The video sits on top
 * and fades in once it plays. hero-video.js starts playback, so reduced-motion visitors and
 * no-JS visitors get the poster only.
 *
 * @package panmotors
 */

$panmotors_eyebrow   = panmotors_field( 'hero_eyebrow' );
$panmotors_title     = panmotors_field( 'hero_title' );
$panmotors_poster_id = (int) panmotors_field( 'hero_poster', false, 0 );
$panmotors_video_id  = (int) panmotors_field( 'hero_video', false, 0 );
$panmotors_video_url = $panmotors_video_id ? wp_get_attachment_url( $panmotors_video_id ) : '';
$panmotors_cta_label = panmotors_field( 'hero_cta_label' );
$panmotors_cta_link  = panmotors_field( 'hero_cta_link', false, panmotors_page_url( 'featured' ) );

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
			<video class="pm-hero__video" src="<?php echo esc_url( $panmotors_video_url ); ?>" muted loop playsinline preload="metadata" tabindex="-1"></video>
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
