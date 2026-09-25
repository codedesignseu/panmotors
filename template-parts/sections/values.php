<?php
/**
 * Our Values section (theme-map 4.5). Values live on the About page (D10); each card links there.
 *
 * Args:
 * - page_id (int)    About page.
 * - context (string) 'home' (built) or 'page' (after the About page is designed).
 *
 * @package panmotors
 */

$panmotors_page_id = (int) ( $args['page_id'] ?? 0 );
$panmotors_values  = $panmotors_page_id ? panmotors_rows( 'values', $panmotors_page_id ) : array();

if ( ! $panmotors_values ) {
	return;
}

$panmotors_url   = get_permalink( $panmotors_page_id );
$panmotors_title = panmotors_field( 'home_values_title', (int) get_option( 'page_on_front' ) );
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
			<a class="pm-value" href="<?php echo esc_url( $panmotors_url ); ?>">
				<span class="pm-value__top">
					<span class="pm-value__index"><?php echo esc_html( $panmotors_v_index ); ?></span>
					<span class="pm-value__arrow" aria-hidden="true">&rarr;</span>
				</span>
				<h3 class="pm-value__title"><?php echo esc_html( $panmotors_v_title ); ?></h3>
				<?php if ( $panmotors_v_body ) : ?>
					<p class="pm-value__body"><?php echo esc_html( $panmotors_v_body ); ?></p>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
</section>
