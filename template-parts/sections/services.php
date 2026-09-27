<?php
/**
 * What We Do (About, inner-pages §5): a row of photo tiles. On hover a tile grows (flex 2.2) and its
 * photo zooms; under 880px the tiles stack.
 *
 * Args (from blocks/services/render.php):
 * - title    (string)  Heading.
 * - services (array[]) Rows: index, title, body, image.
 *
 * @package panmotors
 */

$panmotors_services = array_filter(
	(array) ( $args['services'] ?? array() ),
	static fn( $row ) => '' !== trim( (string) ( $row['title'] ?? '' ) )
);

if ( ! $panmotors_services ) {
	return;
}

$panmotors_title = trim( (string) ( $args['title'] ?? '' ) );
?>
<section class="pm-services pm-pad"<?php echo $panmotors_title ? ' aria-labelledby="services-title"' : ''; ?>>
	<?php if ( $panmotors_title ) : ?>
		<h2 class="pm-title pm-services__title" id="services-title" data-rise><?php echo esc_html( $panmotors_title ); ?></h2>
	<?php endif; ?>

	<div class="pm-services__tiles">
		<?php foreach ( $panmotors_services as $panmotors_service ) : ?>
			<?php
			$panmotors_s_image = (int) ( $panmotors_service['image'] ?? 0 );
			$panmotors_s_index = trim( (string) ( $panmotors_service['index'] ?? '' ) );
			$panmotors_s_body  = trim( (string) ( $panmotors_service['body'] ?? '' ) );
			?>
			<article class="pm-service" data-zoom>
				<?php if ( $panmotors_s_image ) : ?>
					<div class="pm-media pm-service__media">
						<?php
						echo wp_get_attachment_image(
							$panmotors_s_image,
							'pm-tile',
							false,
							array(
								'sizes'   => '(max-width: 880px) calc(100vw - 40px), 50vw',
								'loading' => 'lazy',
							)
						);
						?>
					</div>
				<?php endif; ?>
				<div class="pm-service__shade" aria-hidden="true"></div>
				<div class="pm-service__copy">
					<?php if ( $panmotors_s_index ) : ?>
						<p class="pm-service__index"><?php echo esc_html( $panmotors_s_index ); ?></p>
					<?php endif; ?>
					<h3 class="pm-service__title"><?php echo esc_html( trim( (string) $panmotors_service['title'] ) ); ?></h3>
					<?php if ( $panmotors_s_body ) : ?>
						<p class="pm-service__body"><?php echo esc_html( $panmotors_s_body ); ?></p>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
