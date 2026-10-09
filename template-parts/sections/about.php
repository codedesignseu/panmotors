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
 * The frame is template-parts/components/media-video.php, shared with the Story block.
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
		<?php
		get_template_part(
			'template-parts/components/media-video',
			null,
			array(
				'class' => 'pm-about__media',
				'image' => $panmotors_image,
				'video' => $panmotors_video,
			)
		);
		?>

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
