<?php
/**
 * Swap every image/video field that shows on the homepage for a test attachment, check the
 * homepage shows the new file, then restore.
 * Run: wp eval-file dev/tests/media-fields.php <image id> <video id>  (import two uniquely named files first)
 *
 * @package panmotors
 */

$front = (int) get_option( 'page_on_front' );
$swap  = array(
	'hero_poster (Home)'          => array( 'hero_poster', $front, 'image' ),
	'hero_video (Home)'           => array( 'hero_video', $front, 'video' ),
	'about_image (About)'         => array( 'about_image', panmotors_page( 'about' ), 'image' ),
	'featured car 1 photo'        => array( 'featured_cars_0_image', panmotors_page( 'featured' ), 'image' ),
	'latest car 1 photo'          => array( 'latest_cars_0_image', panmotors_page( 'latest' ), 'image' ),
	'live post 3 photo (Home)'    => array( 'live_posts_2_image', $front, 'image' ),
	'live post 1 video (Home)'    => array( 'live_posts_0_video', $front, 'video' ),
	'showroom photos (Showroom)'  => array( 'showroom_photos', panmotors_page( 'showroom' ), 'gallery' ),
);
$images = array( (int) $args[0] );
$videos = array( (int) $args[1] );
$fetch  = static fn() => wp_remote_retrieve_body( wp_remote_get( home_url( '/?nocache=' . wp_rand() ) ) );
$base   = $fetch();
$fname  = static fn( $id ) => preg_replace( '/-scaled$/', '', pathinfo( get_attached_file( $id ), PATHINFO_FILENAME ) );
// Only use replacements that are not on the homepage already, so "shown" proves the swap.
$images = array_values( array_filter( $images, static fn( $id ) => false === strpos( $base, $fname( $id ) . '-' ) && false === strpos( $base, $fname( $id ) . '.' ) ) );
$videos = array_values( array_filter( $videos, static fn( $id ) => false === strpos( $base, $fname( $id ) . '.' ) ) );
echo 'Unused images: ' . implode( ', ', array_map( $fname, $images ) ) . "\nUnused videos: " . ( implode( ', ', array_map( $fname, $videos ) ) ?: 'none' ) . "\n";

foreach ( $swap as $label => list( $meta, $post_id, $kind ) ) {
	$old  = get_post_meta( $post_id, $meta, true );
	$pool = 'video' === $kind ? $videos : $images;
	$cur  = is_array( $old ) ? (int) $old[0] : (int) $old;
	$new  = (int) current( array_diff( $pool, array( $cur ), 'gallery' === $kind ? (array) $old : array() ) );
	if ( ! $new ) {
		printf( "SKIP    %s (no unused %s to swap in)\n", str_pad( $label, 28 ), $kind );
		continue;
	}
	update_post_meta( $post_id, $meta, 'gallery' === $kind ? array_merge( array( (string) $new ), array_slice( (array) $old, 1 ) ) : $new );
	$file  = pathinfo( get_attached_file( $new ), PATHINFO_FILENAME );
	$shown = false !== strpos( $fetch(), preg_replace( '/-scaled$/', '', $file ) );
	update_post_meta( $post_id, $meta, $old );
	printf( "%s %s → %s\n", $shown ? 'SHOWN ' : 'MISSING', str_pad( $label, 28 ), $file );
}
$logo = (int) get_theme_mod( 'custom_logo' );
set_theme_mod( 'custom_logo', $images[0] );
$lf = pathinfo( get_attached_file( $images[0] ), PATHINFO_FILENAME );
printf( "%s %s → %s\n", false !== strpos( $fetch(), preg_replace( '/-scaled$/', '', $lf ) ) ? 'SHOWN ' : 'MISSING', str_pad( 'logo (Customizer)', 28 ), $lf );
set_theme_mod( 'custom_logo', $logo );
