<?php
/**
 * About section (theme-map 4.6). Photo, heading and stats from the block, paragraph from the options.
 *
 * The paragraph is the one-sentence description. With the fade on, the background turns
 * ink → paper while scrolling (heritage-fade.js).
 *
 * Media type Video (about-video.js): the photo is the poster and the fallback. An uploaded clip
 * (muted, looped, preload="none") fades in over it and plays only while the section is in view,
 * with a pause / play button; under reduced motion it waits for that button. A YouTube or Vimeo
 * player waits in a <template> until the play button on the poster is pressed (they set cookies).
 *
 * Args (from blocks/about/render.php): image (photo or poster), video (kind 'upload' with src and
 * type, or 'embed' with src; empty for a photo), eyebrow, title, text, stats (rows: value, label),
 * fade.
 *
 * @package panmotors
 */

$panmotors_image   = (int) ( $args['image'] ?? 0 );
$panmotors_video   = (array) ( $args['video'] ?? array() );
$panmotors_kind    = (string) ( $panmotors_video['kind'] ?? '' );
$panmotors_hook    = array(
	'upload' => ' data-about-video',
	'embed'  => ' data-about-embed',
)[ $panmotors_kind ] ?? '';
$panmotors_eyebrow = (string) ( $args['eyebrow'] ?? '' );
$panmotors_title   = (string) ( $args['title'] ?? '' );
$panmotors_text    = (string) ( $args['text'] ?? '' );
$panmotors_stats   = array_filter(
	(array) ( $args['stats'] ?? array() ),
	static fn( $row ) => '' !== trim( (string) ( $row['value'] ?? '' ) )
);

if ( ! $panmotors_title && ! $panmotors_image && ! $panmotors_kind ) {
	return;
}
?>
<section id="heritage" class="pm-about pm-pad pm-pad-y"<?php echo $panmotors_title ? ' aria-labelledby="about-title"' : ''; ?><?php echo ! empty( $args['fade'] ) ? ' data-heritage-fade' : ''; ?>>
	<div class="pm-about__grid">
		<?php if ( $panmotors_image || $panmotors_kind ) : ?>
			<div class="pm-media pm-about__media" data-rise-l data-zoom<?php echo $panmotors_hook; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed strings. ?>>
				<?php
				if ( $panmotors_image ) {
					echo wp_get_attachment_image(
						$panmotors_image,
						'pm-portrait',
						false,
						array(
							'sizes'   => '(max-width: 808px) calc(100vw - 40px), 50vw',
							'loading' => 'lazy',
						)
					);
				}
				if ( 'upload' === $panmotors_kind ) {
					printf(
						'<video class="pm-about__video" src="%1$s" muted loop playsinline preload="none" tabindex="-1" aria-hidden="true"></video>' .
						'<button class="pm-about__toggle" type="button" aria-label="%2$s" data-play="%2$s" data-pause="%3$s" data-about-toggle hidden><span class="pm-about__icon" aria-hidden="true"></span></button>',
						esc_url( $panmotors_video['src'] ),
						esc_attr__( 'Play video', 'panmotors' ),
						esc_attr__( 'Pause video', 'panmotors' )
					);
				} elseif ( 'embed' === $panmotors_kind ) {
					$panmotors_alt = $panmotors_image ? trim( (string) get_post_meta( $panmotors_image, '_wp_attachment_image_alt', true ) ) : '';
					printf(
						'<button class="pm-about__play" type="button" aria-label="%1$s" data-about-play hidden><span class="pm-about__icon" aria-hidden="true"></span></button>' .
						'<template data-about-frame><iframe class="pm-about__iframe" src="%2$s" title="%3$s" allow="autoplay; fullscreen; picture-in-picture; encrypted-media" allowfullscreen tabindex="0"></iframe></template>',
						/* translators: %s: what the video shows (the poster's alt text). */
						esc_attr( $panmotors_alt ? sprintf( __( 'Play video: %s', 'panmotors' ), $panmotors_alt ) : __( 'Play video', 'panmotors' ) ),
						esc_url( $panmotors_video['src'] ),
						esc_attr( $panmotors_alt ? $panmotors_alt : __( 'Video', 'panmotors' ) )
					);
				}
				?>
			</div>
		<?php endif; ?>

		<div class="pm-about__copy" data-rise>
			<?php if ( $panmotors_eyebrow ) : ?>
				<p class="pm-eyebrow pm-about__eyebrow"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
			<?php endif; ?>
			<?php if ( $panmotors_title ) : ?>
				<h2 class="pm-about__title" id="about-title"><?php echo esc_html( $panmotors_title ); ?></h2>
			<?php endif; ?>
			<?php if ( $panmotors_text ) : ?>
				<p class="pm-about__text"><?php echo esc_html( $panmotors_text ); ?></p>
			<?php endif; ?>

			<?php if ( $panmotors_stats ) : ?>
				<dl class="pm-stats">
					<?php foreach ( $panmotors_stats as $panmotors_stat ) : ?>
						<div class="pm-stats__item">
							<dt class="pm-stats__value"><?php echo esc_html( $panmotors_stat['value'] ); ?></dt>
							<?php if ( ! empty( $panmotors_stat['label'] ) ) : ?>
								<dd class="pm-stats__label"><?php echo esc_html( $panmotors_stat['label'] ); ?></dd>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
		</div>
	</div>
</section>
