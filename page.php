<?php
/**
 * Default page template: page hero with breadcrumb, then the editor content in prose styles.
 * Used for the Privacy and Cookie policy pages.
 *
 * @package panmotors
 */

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/components/page-hero' );
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'pm-page-body pm-light pm-pad' ); ?>>
		<div class="pm-prose">
			<?php
			the_content();

			wp_link_pages(
				array(
					'before' => '<nav class="pm-pagination" aria-label="' . esc_attr__( 'Page sections', 'panmotors' ) . '">',
					'after'  => '</nav>',
				)
			);
			?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
