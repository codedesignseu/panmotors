<?php
/**
 * About section (theme-map 4.6). Photo, heading and stats from the block, paragraph from the options.
 *
 * The paragraph is the one-sentence description. With the fade on, the background turns
 * ink → paper while scrolling (heritage-fade.js).
 *
 * Args (from blocks/about/render.php): image, eyebrow, title, text, stats (rows: value, label), fade.
 *
 * @package panmotors
 */

$panmotors_image   = (int) ( $args['image'] ?? 0 );
$panmotors_eyebrow = (string) ( $args['eyebrow'] ?? '' );
$panmotors_title   = (string) ( $args['title'] ?? '' );
$panmotors_text    = (string) ( $args['text'] ?? '' );
$panmotors_stats   = array_filter(
	(array) ( $args['stats'] ?? array() ),
	static fn( $row ) => '' !== trim( (string) ( $row['value'] ?? '' ) )
);

if ( ! $panmotors_title && ! $panmotors_image ) {
	return;
}
?>
<section id="heritage" class="pm-about pm-pad pm-pad-y" aria-labelledby="about-title"<?php echo ! empty( $args['fade'] ) ? ' data-heritage-fade' : ''; ?>>
	<div class="pm-about__grid">
		<?php if ( $panmotors_image ) : ?>
			<div class="pm-media pm-about__media" data-rise-l data-zoom>
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

		<div class="pm-about__copy" data-rise>
			<?php if ( $panmotors_eyebrow ) : ?>
				<p class="pm-eyebrow pm-about__eyebrow"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
			<?php endif; ?>
			<h2 class="pm-about__title" id="about-title"><?php echo esc_html( $panmotors_title ); ?></h2>
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
