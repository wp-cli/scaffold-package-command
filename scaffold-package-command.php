<?php

use WP_CLI\ScaffoldPackageCommand;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

WP_CLI::add_command( 'scaffold package', [ ScaffoldPackageCommand::class, 'package' ] );
WP_CLI::add_command( 'scaffold package-readme', [ ScaffoldPackageCommand::class, 'package_readme' ] );
WP_CLI::add_command( 'scaffold package-tests', [ ScaffoldPackageCommand::class, 'package_tests' ] );
WP_CLI::add_command( 'scaffold package-github', [ ScaffoldPackageCommand::class, 'package_github' ] );
