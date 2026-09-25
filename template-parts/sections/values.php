<?php
/**
 * Our Values section (theme-map 4.5). Used through the "Our Values" synced pattern (D11), so
 * Home and About show the same cards. Each card links to the chosen page, or is not a link.
 *
 * Args (from blocks/values/render.php):
 * - title  (string)  Heading.
 * - url    (string)  Page the cards open, or ''.
 * - values (array[]) Rows: index, title, body.
 *
 * @package panmotors
 */

$panmotors_values = (array) ( $args['values'] ?? array() );

if ( ! $panmotors_values ) {
	return;
}

$panmotors_url   = (string) ( $args['url'] ?? '' );
$panmotors_title = (string) ( $args['title'] ?? '' );
$panmotors_tag   = $panmotors_url ? 'a' : 'div';
?>
<section id="ways" class="pm-values pm-pad pm-pad-y" aria-labelledby="values-title">
	<?php if ( $panmotors_title ) : ?>
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
			<<?php echo $panmotors_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 'a' or 'div'. ?> class="pm-value"<?php echo $panmotors_url ? ' href="' . esc_url( $panmotors_url ) . '"' : ''; ?>>
				<span class="pm-value__top">
					<span class="pm-value__index"><?php echo esc_html( $panmotors_v_index ); ?></span>
					<span class="pm-value__arrow" aria-hidden="true">&rarr;</span>
				</span>
				<h3 class="pm-value__title"><?php echo esc_html( $panmotors_v_title ); ?></h3>
				<?php if ( $panmotors_v_body ) : ?>
					<p class="pm-value__body"><?php echo esc_html( $panmotors_v_body ); ?></p>
				<?php endif; ?>
			</<?php echo $panmotors_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php endforeach; ?>
	</div>
</section>
