<?php
/**
 * Homepage: the Home page's blocks (D11). Each pm/* block prints its section as in the design
 * and loads its own JS module; a hidden block prints nothing.
 * Home matches _design/index.html exactly: nothing is added that the design does not have.
 *
 * @package panmotors
 */

get_header();

while ( have_posts() ) {
	the_post();
	panmotors_the_blocks();
}

get_footer();
