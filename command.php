<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

require_once __DIR__ . '/inc/class-wp-cli-scaffold-package-command.php';

WP_CLI::add_command( 'scaffold package', 'WP_CLI_Scaffold_Package_Command' );
