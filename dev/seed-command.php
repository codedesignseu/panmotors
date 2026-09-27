<?php
/**
 * WP-CLI command for the seed, so it can take a --reset-demo flag (wp eval-file accepts no flags).
 * Development only, excluded from deploys via .distignore.
 *
 *   wp --require=dev/seed-command.php panmotors seed --user=1
 *   wp --require=dev/seed-command.php panmotors seed --user=1 --reset-demo
 *
 * @package panmotors
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command(
	'panmotors seed',
	static function ( $args, $assoc_args ) {
		define( 'PANMOTORS_SEED_RESET', ! empty( $assoc_args['reset-demo'] ) );
		require __DIR__ . '/seed.php';
	},
	array(
		'shortdesc' => 'Demo content: creates what is missing, keeps everything else. Exports the database first.',
		'synopsis'  => array(
			array(
				'type'        => 'flag',
				'name'        => 'reset-demo',
				'optional'    => true,
				'description' => 'Overwrite demo content with the seed version. Refused on production.',
			),
		),
	)
);
