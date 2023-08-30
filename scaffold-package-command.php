<?php

use WP_CLI\ScaffoldPackageCommand;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_scaffold_package_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $wpcli_scaffold_package_autoload ) ) {
	require_once $wpcli_scaffold_package_autoload;
}

WP_CLI::add_command( 'scaffold package', [ ScaffoldPackageCommand::class, 'package' ] );
WP_CLI::add_command( 'scaffold package-readme', [ ScaffoldPackageCommand::class, 'package_readme' ] );
WP_CLI::add_command( 'scaffold package-tests', [ ScaffoldPackageCommand::class, 'package_tests' ] );
WP_CLI::add_command( 'scaffold package-github', [ ScaffoldPackageCommand::class, 'package_github' ] );
