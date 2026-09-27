<?php
/**
 * Our Values section (theme-map 4.5, inner-pages §3). One set of values (Options), two looks:
 * - dark:  outlined cards on the dark page with an arrow row; each card links to the chosen page
 *          (homepage, _design/v2/index.html).
 * - light: a paper panel with the heading and a short intro; cards are not links
 *          (About, _design/v2/about.html).
 *
 * Args (from blocks/values/render.php):
 * - style  (string)  'dark' or 'light'.
 * - title  (string)  Heading.
 * - intro  (string)  Short intro (light).
 * - url    (string)  Page the cards open, or '' (dark).
 * - values (array[]) Rows: index, title, body.
 *
 * @package panmotors
 */

$panmotors_values = (array) ( $args['values'] ?? array() );

if ( ! $panmotors_values ) {
	return;
}

$panmotors_light = 'light' === ( $args['style'] ?? '' );
$panmotors_url   = $panmotors_light ? '' : (string) ( $args['url'] ?? '' );
$panmotors_title = (string) ( $args['title'] ?? '' );
$panmotors_intro = (string) ( $args['intro'] ?? '' );
$panmotors_tag   = $panmotors_url ? 'a' : 'div';
$panmotors_id    = $panmotors_light ? 'values' : 'ways';
?>
<section id="<?php echo esc_attr( $panmotors_id ); ?>" class="pm-values<?php echo $panmotors_light ? ' pm-values--light pm-light' : ''; ?> pm-pad pm-pad-y"<?php echo $panmotors_title ? ' aria-labelledby="values-title"' : ''; ?>>
	<?php if ( $panmotors_light ) : ?>
		<?php if ( $panmotors_title || $panmotors_intro ) : ?>
			<div class="pm-section-head" data-rise>
				<?php if ( $panmotors_title ) : ?>
					<h2 class="pm-title" id="values-title"><?php echo esc_html( $panmotors_title ); ?></h2>
				<?php endif; ?>
				<?php if ( $panmotors_intro ) : ?>
					<p class="pm-lede"><?php echo esc_html( $panmotors_intro ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	<?php elseif ( $panmotors_title ) : ?>
		<h2 class="pm-title pm-values__title" id="values-title" data-rise><?php echo esc_html( $panmotors_title ); ?></h2>
	<?php endif; ?>

	<div class="pm-values__grid">
		<?php foreach ( $panmotors_values as $panmotors_value ) : ?>
			<?php
			$panmotors_v_title = trim( (string) ( $panmotors_value['title'] ?? '' ) );
			if ( ! $panmotors_v_title ) {
				continue;
			}
			$panmotors_v_index = trim( (string) ( $panmotors_value['index'] ?? '' ) );
			$panmotors_v_body  = trim( (string) ( $panmotors_value['body'] ?? '' ) );
			?>
			<<?php echo $panmotors_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 'a' or 'div'. ?> class="pm-value"<?php echo $panmotors_url ? ' href="' . esc_url( $panmotors_url ) . '"' : ''; ?><?php echo $panmotors_light ? ' data-rise' : ''; ?>>
				<span class="pm-value__top">
					<span class="pm-value__index"><?php echo esc_html( $panmotors_v_index ); ?></span>
					<?php if ( ! $panmotors_light ) : ?>
						<span class="pm-value__arrow" aria-hidden="true">&rarr;</span>
					<?php endif; ?>
				</span>
				<h3 class="pm-value__title"><?php echo esc_html( $panmotors_v_title ); ?></h3>
				<?php if ( $panmotors_v_body ) : ?>
					<p class="pm-value__body"><?php echo esc_html( $panmotors_v_body ); ?></p>
				<?php endif; ?>
			</<?php echo $panmotors_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php endforeach; ?>
	</div>
</section>
