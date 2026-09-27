<?php
/**
 * Page template. Pages built from pm/* blocks (D11) print their blocks; the page title becomes the
 * H1 through pm/page-header (or the older pm/page-hero), or a title-only page hero when the page has
 * neither. Pages with plain
 * editor content only (Privacy and Cookie policy) keep the prose layout.
 *
 * @package panmotors
 */

get_header();

while ( have_posts() ) :
	the_post();

	if ( false !== strpos( (string) get_post_field( 'post_content' ), '<!-- wp:pm/' ) ) :
		if ( ! has_block( 'pm/page-hero' ) && ! has_block( 'pm/page-header' ) ) {
			get_template_part( 'template-parts/components/page-hero' );
		}
		?>
		<div class="pm-blocks">
			<?php panmotors_the_blocks(); ?>
		</div>
		<?php
	else :
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
	endif;
endwhile;

get_footer();
