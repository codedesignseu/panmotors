<?php
/**
 * Inner page intro text (page_body). The page's own SEO copy, in prose styles.
 *
 * Args:
 * - page_id (int) Page to read. Defaults to the current post.
 *
 * @package panmotors
 */

$panmotors_body = panmotors_field( 'page_body', (int) ( $args['page_id'] ?? get_the_ID() ) );

if ( ! $panmotors_body ) {
	return;
}
?>
<div class="pm-page-intro pm-pad">
	<div class="pm-prose pm-page-intro__text">
		<?php echo wp_kses_post( $panmotors_body ); ?>
	</div>
</div>
