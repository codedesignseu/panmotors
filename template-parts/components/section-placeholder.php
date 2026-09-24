<?php
/**
 * Temporary placeholder where a section component will render. Removed as each component is built.
 *
 * Args:
 * - label (string) Section name.
 *
 * @package panmotors
 */

?>
<div class="pm-placeholder pm-pad">
	<p class="pm-placeholder__box pm-meta">
		<?php
		/* translators: %s: section name. */
		printf( esc_html__( '%s section: component coming next', 'panmotors' ), esc_html( $args['label'] ?? '' ) );
		?>
	</p>
</div>
