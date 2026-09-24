<?php
/**
 * About section (theme-map 4.6). Image and stats from the About page, paragraph from the options.
 *
 * On home the paragraph is the one-sentence description only (the longer story is for the
 * About page). The background fades ink → paper while scrolling (heritage-fade.js).
 *
 * Args:
 * - page_id (int)    About page.
 * - context (string) 'home' (built) or 'page' (after the About page is designed).
 *
 * @package panmotors
 */

$panmotors_page_id = (int) ( $args['page_id'] ?? 0 );

if ( ! $panmotors_page_id ) {
	return;
}

$panmotors_image   = (int) panmotors_field( 'about_image', $panmotors_page_id, 0 );
$panmotors_eyebrow = panmotors_field( 'page_eyebrow', $panmotors_page_id );
$panmotors_title   = panmotors_field( 'home_about_title', (int) get_option( 'page_on_front' ), get_the_title( $panmotors_page_id ) );
$panmotors_text    = panmotors_option( 'description' );
$panmotors_stats   = array_filter(
	panmotors_rows( 'about_stats', $panmotors_page_id ),
	static fn( $row ) => '' !== trim( (string) ( $row['value'] ?? '' ) )
);
?>
<section id="heritage" class="pm-about pm-pad pm-pad-y" aria-labelledby="about-title" data-heritage-fade>
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
