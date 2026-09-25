<?php
/**
 * Pan Motors Live (theme-map 4.8). Homepage only (D10): posts on Home, Instagram URL in options.
 *
 * Posts are entered by hand (no Instagram API). Each tile's link name is its visible stats
 * and caption plus hidden context ("Instagram post:", "opens in a new tab"). Video tiles load their src when they near the
 * viewport and play only while 35% visible (live-videos.js). Reduced motion: poster only.
 * Editor preview: the still photo instead of the video.
 *
 * Args (from blocks/live/render.php): eyebrow, title, cta_label, posts (rows: type, video,
 * image, url, caption, likes, comments), preview.
 *
 * @package panmotors
 */

$panmotors_posts = array_filter(
	(array) ( $args['posts'] ?? array() ),
	static fn( $row ) => ! empty( $row['url'] ) && ( ! empty( $row['image'] ) || ! empty( $row['video'] ) )
);

if ( ! $panmotors_posts ) {
	return;
}

$panmotors_eyebrow   = (string) ( $args['eyebrow'] ?? '' );
$panmotors_title     = (string) ( $args['title'] ?? '' );
$panmotors_cta_label = (string) ( $args['cta_label'] ?? '' );
$panmotors_insta     = panmotors_option( 'instagram_url' );
$panmotors_preview   = ! empty( $args['preview'] );
?>
<section id="live" class="pm-live pm-pad" aria-labelledby="live-title">
	<div class="pm-section-head pm-live__head" data-rise>
		<div>
			<?php if ( $panmotors_eyebrow ) : ?>
				<p class="pm-eyebrow"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
			<?php endif; ?>
			<?php if ( $panmotors_title ) : ?>
				<h2 class="pm-title" id="live-title"><?php echo esc_html( $panmotors_title ); ?></h2>
			<?php endif; ?>
		</div>
		<?php if ( $panmotors_insta && $panmotors_cta_label ) : ?>
			<a class="pm-pill" href="<?php echo esc_url( $panmotors_insta ); ?>" target="_blank" rel="noopener">
				<?php echo esc_html( $panmotors_cta_label ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'panmotors' ); ?></span>
			</a>
		<?php endif; ?>
	</div>

	<div class="pm-live__grid">
		<?php foreach ( $panmotors_posts as $panmotors_post ) : ?>
			<?php
			$panmotors_caption  = trim( (string) ( $panmotors_post['caption'] ?? '' ) );
			$panmotors_likes    = trim( (string) ( $panmotors_post['likes'] ?? '' ) );
			$panmotors_comments = trim( (string) ( $panmotors_post['comments'] ?? '' ) );
			$panmotors_image    = (int) ( $panmotors_post['image'] ?? 0 );
			$panmotors_video    = ! $panmotors_preview && 'video' === ( $panmotors_post['type'] ?? '' ) && ! empty( $panmotors_post['video'] ) ? wp_get_attachment_url( (int) $panmotors_post['video'] ) : '';
			?>
			<a class="pm-post" href="<?php echo esc_url( $panmotors_post['url'] ); ?>" target="_blank" rel="noopener" data-zoom>
				<span class="screen-reader-text"><?php esc_html_e( 'Instagram post:', 'panmotors' ); ?> </span>
				<?php if ( $panmotors_video ) : ?>
					<?php $panmotors_poster = $panmotors_image ? wp_get_attachment_image_url( $panmotors_image, 'pm-portrait' ) : ''; ?>
					<video class="pm-post__media" muted loop playsinline preload="none" tabindex="-1" data-live-video data-src="<?php echo esc_url( $panmotors_video ); ?>"<?php echo $panmotors_poster ? ' data-poster="' . esc_url( $panmotors_poster ) . '"' : ''; ?>></video>
				<?php elseif ( $panmotors_image ) : ?>
					<?php
					echo wp_get_attachment_image(
						$panmotors_image,
						'pm-portrait',
						false,
						array(
							'class'   => 'pm-post__media',
							'alt'     => '',
							'sizes'   => '(max-width: 700px) calc(100vw - 40px), 363px',
							'loading' => 'lazy',
						)
					);
					?>
				<?php endif; ?>
				<span class="pm-post__shade" aria-hidden="true"></span>
				<span class="pm-post__meta">
					<?php if ( $panmotors_likes ) : ?>
						<span><span aria-hidden="true">&#9829;</span> <?php echo esc_html( $panmotors_likes ); ?><span class="screen-reader-text"> <?php esc_html_e( 'likes', 'panmotors' ); ?></span></span>
					<?php endif; ?>
					<?php if ( $panmotors_comments ) : ?>
						<span><span aria-hidden="true">&#9998;</span> <?php echo esc_html( $panmotors_comments ); ?><span class="screen-reader-text"> <?php esc_html_e( 'comments', 'panmotors' ); ?></span></span>
					<?php endif; ?>
					<?php if ( $panmotors_caption ) : ?>
						<span class="pm-post__caption"><?php echo esc_html( $panmotors_caption ); ?></span>
					<?php endif; ?>
				</span>
				<span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'panmotors' ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
