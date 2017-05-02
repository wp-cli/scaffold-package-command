<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$registration = function () {
	$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';
	if ( file_exists( $autoload ) ) {
		require_once $autoload;
	}

	WP_CLI::add_command( 'scaffold package', array( 'WP_CLI\ScaffoldPackageCommand', 'package' ) );
	WP_CLI::add_command( 'scaffold package-readme', array( 'WP_CLI\ScaffoldPackageCommand', 'package_readme' ) );
	WP_CLI::add_command( 'scaffold package-tests', array( 'WP_CLI\ScaffoldPackageCommand', 'package_tests' ) );
};

// Only use command hooks in versions that support them.
if ( version_compare( WP_CLI_VERSION, '1.2.0-alpha', '>=' ) ) {
	WP_CLI::add_hook( 'after_add_command:scaffold', $registration );
} else {
	$registration();
}
