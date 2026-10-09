<?php
/**
 * A 4:5 media frame with a photo, or a video over its poster (about-video.js): Home About
 * (pm/about) and About Story (pm/story) share it, with the same classes (pm-about__video,
 * pm-about__toggle, pm-about__play, pm-about__iframe) and hooks.
 *
 * Upload: a muted, looped clip (preload="none") that fades in over the poster while the section is
 * in view, with a pause / play button. YouTube or Vimeo: the player waits in a <template> until the
 * play button on the poster is pressed (they set cookies).
 *
 * Args:
 * - class (string) Frame class, e.g. 'pm-about__media'.
 * - image (int)    Photo, or the poster of a video.
 * - video (array)  From panmotors_block_video(): kind 'upload' (src, type) or 'embed' (src); empty for a photo.
 *
 * @package panmotors
 */

$panmotors_image = (int) ( $args['image'] ?? 0 );
$panmotors_video = (array) ( $args['video'] ?? array() );
$panmotors_kind  = (string) ( $panmotors_video['kind'] ?? '' );
$panmotors_hook  = array(
	'upload' => ' data-about-video',
	'embed'  => ' data-about-embed',
)[ $panmotors_kind ] ?? '';

if ( ! $panmotors_image && ! $panmotors_kind ) {
	return;
}
?>
<div class="pm-media <?php echo esc_attr( (string) ( $args['class'] ?? '' ) ); ?>" data-rise-l data-zoom<?php echo $panmotors_hook; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed strings. ?>>
	<?php
	if ( $panmotors_image ) {
		echo wp_get_attachment_image(
			$panmotors_image,
			'pm-portrait',
			false,
			array(
				'sizes'   => '(max-width: 808px) calc(100vw - 40px), 50vw',
				'loading' => 'lazy',
			)
		);
	}
	if ( 'upload' === $panmotors_kind ) {
		printf(
			'<video class="pm-about__video" src="%1$s" muted loop playsinline preload="none" tabindex="-1" aria-hidden="true"></video>' .
			'<button class="pm-about__toggle" type="button" aria-label="%2$s" data-play="%2$s" data-pause="%3$s" data-about-toggle hidden><span class="pm-about__icon" aria-hidden="true"></span></button>',
			esc_url( $panmotors_video['src'] ),
			esc_attr__( 'Play video', 'panmotors' ),
			esc_attr__( 'Pause video', 'panmotors' )
		);
	} elseif ( 'embed' === $panmotors_kind ) {
		$panmotors_alt = $panmotors_image ? trim( (string) get_post_meta( $panmotors_image, '_wp_attachment_image_alt', true ) ) : '';
		printf(
			'<button class="pm-about__play" type="button" aria-label="%1$s" data-about-play hidden><span class="pm-about__icon" aria-hidden="true"></span></button>' .
			'<template data-about-frame><iframe class="pm-about__iframe" src="%2$s" title="%3$s" allow="autoplay; fullscreen; picture-in-picture; encrypted-media" allowfullscreen tabindex="0"></iframe></template>',
			/* translators: %s: what the video shows (the poster's alt text). */
			esc_attr( $panmotors_alt ? sprintf( __( 'Play video: %s', 'panmotors' ), $panmotors_alt ) : __( 'Play video', 'panmotors' ) ),
			esc_url( $panmotors_video['src'] ),
			esc_attr( $panmotors_alt ? $panmotors_alt : __( 'Video', 'panmotors' ) )
		);
	}
	?>
</div>
