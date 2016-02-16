<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

require_once __DIR__ . '/inc/ScaffoldPackageCommand.php';

WP_CLI::add_command( 'scaffold package', 'WP_CLI\ScaffoldPackageCommand' );
