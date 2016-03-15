<?php

namespace WP_CLI;

use WP_CLI;
use WP_CLI\Process;
use WP_CLI\Utils;

class ScaffoldPackageCommand {

	/**
	 * Generate the files needed for a basic WP-CLI command.
	 *
	 * Default behavior is to create the following files:
	 * - command.php
	 * - composer.json (with package name, description, and license)
	 * - .gitignore and .editorconfig
	 * - README.md (via wp scaffold package-readme)
	 * - Test harness (via wp scaffold package-tests)
	 *
	 * Unless specified with `--dir=<dir>`, the command package is placed in the
	 * WP-CLI package directory.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Name for the new package. Expects <author>/<package> (e.g. 'wp-cli/scaffold-package').
	 *
	 * [--description=<description>]
	 * : Human-readable description for the package.
	 *
	 * [--dir=<dir>]
	 * : Specify a destination directory for the command. Defaults to WP-CLI's packages directory.
	 *
	 * [--license=<license>]
	 * : License for the package. Default: MIT.
	 *
	 * [--skip-tests]
	 * : Don't generate files for integration testing.
	 *
	 * [--skip-readme]
	 * : Don't generate a README.md for the package.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * @when before_wp_load
	 */
	public function package( $args, $assoc_args ) {

		$defaults = array(
			'dir'         => '',
			'description' => '',
			'license'     => 'MIT',
		);
		$assoc_args = array_merge( $defaults, $assoc_args );
		$assoc_args['name'] = $args[0];

		$bits = explode( '/', $assoc_args['name'] );
		if ( 2 !== count( $bits ) || empty( $bits[0] ) || empty( $bits[1] ) ) {
			WP_CLI::error( "'{$assoc_args['name']}' is an invalid package name. Package scaffold expects '<author>/<package>'." );
		}

		if ( ! empty( $assoc_args['dir'] ) ) {
			$package_dir = $assoc_args['dir'];
		} else {
			$package_dir = WP_CLI::get_runner()->get_packages_dir_path() . 'vendor/' . $assoc_args['name'];
		}

		$force = Utils\get_flag_value( $assoc_args, 'force' );

		$package_root = dirname( dirname( __FILE__ ) );
		$template_path = $package_root . '/templates/';

		$files_written = $this->create_files( array(
			"{$package_dir}/.gitignore"     => file_get_contents( "{$package_root}/.gitignore" ),
			"{$package_dir}/.editorconfig"  => file_get_contents( "{$package_root}/.editorconfig" ),
			"{$package_dir}/wp-cli.yml"     => file_get_contents( "{$package_root}/wp-cli.yml" ),
			"{$package_dir}/command.php"    => Utils\mustache_render( "{$template_path}/command.mustache", $assoc_args ),
			"{$package_dir}/composer.json"  => Utils\mustache_render( "{$template_path}/composer.mustache", $assoc_args ),
		), $force );

		if ( empty( $files_written ) ) {
			WP_CLI::log( 'All package files were skipped.' );
		} else {
			WP_CLI::success( "Created package files in {$package_dir}" );
		}

		if ( ! Utils\get_flag_value( $assoc_args, 'skip-tests' ) ) {
			WP_CLI::run_command( array( 'scaffold', 'package-tests', $package_dir ), array( 'force' => $force ) );
		}

		if ( ! Utils\get_flag_value( $assoc_args, 'skip-readme' ) ) {
			WP_CLI::run_command( array( 'scaffold', 'package-readme', $package_dir ), array( 'force' => $force ) );
		}
	}

	/**
	 * Generate a README.md for your command.
	 *
	 * Creates a README.md with Installing, Using, and Contributing instructions
	 * based on the composer.json file for your WP-CLI package.
	 *
	 * Command-specific docs are generated based composer.json -> 'extras'
	 * -> 'commands'.
	 *
	 * ## OPTIONS
	 *
	 * <dir>
	 * : Directory of an existing command.
	 *
	 * [--force]
	 * : Overwrite the readme if it already exists.
	 *
	 * @when before_wp_load
	 * @subcommand package-readme
	 */
	public function package_readme( $args, $assoc_args ) {

		list( $package_dir ) = $args;

		if ( ! is_dir( $package_dir ) || ! file_exists( $package_dir . '/composer.json' ) ) {
			WP_CLI::error( "Invalid package directory. composer.json file must be present." );
		}

		$composer_obj = json_decode( file_get_contents( $package_dir . '/composer.json' ), true );
		if ( ! $composer_obj ) {
			WP_CLI::error( 'Invalid composer.json in package directory.' );
		}

		$force = Utils\get_flag_value( $assoc_args, 'force' );

		$package_root = dirname( dirname( __FILE__ ) );
		$template_path = $package_root . '/templates/';

		$bits = explode( '/', $composer_obj['name'] );
		$readme_args = array(
			'package_name'        => $composer_obj['name'],
			'package_short_name'  => $bits[1],
			'package_name_border' => str_pad( '', strlen( $composer_obj['name'] ), '=' ),
			'package_description' => $composer_obj['description'],
			'has_travis'          => file_exists( $package_dir . '/.travis.yml' ),
			'has_commands'        => false,
		);

		if ( ! empty( $composer_obj['extras']['commands'] ) ) {
			$readme_args['commands'] = array();
			$ret = WP_CLI::launch_self( "cli cmd-dump", array(), array(), false, true );
			$cmd_dump = json_decode( $ret->stdout, true );
			foreach( $composer_obj['extras']['commands'] as $command ) {
				$bits = explode( ' ', $command );
				$parent_command = $cmd_dump;
				do {
					$cmd_bit = array_shift( $bits );
					$found = false;
					foreach( $parent_command['subcommands'] as $subcommand ) {
						if ( $subcommand['name'] === $cmd_bit ) {
							$parent_command = $subcommand;
							$found = true;
							break;
						}
					}
					if ( ! $found ) {
						$parent_command = false;
					}
				} while( $parent_command && $bits );

				$longdesc = preg_replace( '/## GLOBAL PARAMETERS(.+)/s', '', $parent_command['longdesc'] );
				$longdesc = preg_replace( '/##\s(.+)/', '**$1**', $longdesc );

				// definition lists
				$longdesc = preg_replace_callback( '/([^\n]+)\n: (.+?)(\n\n|$)/s', array( __CLASS__, 'rewrap_param_desc' ), $longdesc );

				$readme_args['commands'][] = array(
					'name' => "wp {$command}",
					'shortdesc' => $parent_command['description'],
					'synopsis' => "wp {$command} {$parent_command['synopsis']}",
					'longdesc' => $longdesc,
				);
			}
			$readme_args['has_commands'] = true;
			$readme_args['has_multiple_commands'] = count( $readme_args['commands'] ) > 1 ? true : false;
		}

		$files_written = $this->create_files( array(
			"{$package_dir}/README.md" => Utils\mustache_render( "{$template_path}/readme.mustache", $readme_args ),
		), $force );

		if ( empty( $files_written ) ) {
			WP_CLI::log( 'Package readme generation skipped.' );
		} else {
			WP_CLI::success( 'Created package readme.' );
		}
	}

	/**
	 * Generate files needed for writing Behat tests for your command.
	 *
	 * WP-CLI makes use of a Behat-based testing framework, which you should use
	 * too. Behat is a great choice for your WP-CLI commands because:
	 *
	 * * It’s easy to write new tests, which means they’ll actually get written.
	 * * The tests interface with your command in the same manner as your users
	 * interface with your command.
	 *
	 * Behat tests live in the `features/` directory of your project. When you
	 * use this command, it will generate a default test that looks like this:
	 *
	 * ```
	 * Feature: Test that WP-CLI loads.
	 *
	 *   Scenario: WP-CLI loads for your tests
	 *     Given a WP install
	 *
	 *     When I run `wp eval 'echo "Hello world.";'`
	 *     Then STDOUT should contain:
	 *       """
	 *       Hello world.
	 *       """
	 * ```
	 *
	 * This command generates all of the files needed for you to write Behat
	 * tests for your own command. Specifically:
	 *
	 * * `.travis.yml` is the configuration file for Travis CI.
	 * * `bin/install-package-tests.sh` will configure your environment to run
	 * the tests.
	 * * `features/load-wp-cli.feature` is a basic test to confirm WP-CLI can
	 * load.
	 * * `features/bootstrap`, `features/steps`, `features/extra` are Behat
	 * configuration files.
	 *
	 * ## ENVIRONMENT
	 *
	 * The `features/bootstrap/FeatureContext.php` file expects the WP_CLI_BIN_DIR and WP_CLI_CONFIG_PATH environment variables.
	 *
	 * WP-CLI Behat framework uses Behat ~2.5.
	 *
	 * ## OPTIONS
	 *
	 * <dir>
	 * : The package directory to generate tests for.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * ## EXAMPLE
	 *
	 *     wp scaffold package-tests /path/to/command/dir/
	 *
	 * @when       before_wp_load
	 * @subcommand package-tests
	 */
	public function package_tests( $args, $assoc_args ) {
		list( $package_dir ) = $args;

		if ( is_file( $package_dir ) ) {
			$package_dir = dirname( $package_dir );
		} else if ( is_dir( $package_dir ) ) {
			$package_dir = rtrim( $package_dir, '/' );
		}

		if ( ! is_dir( $package_dir ) || ! file_exists( $package_dir . '/composer.json' ) ) {
			WP_CLI::error( "Invalid package directory. composer.json file must be present." );
		}

		$package_dir .= '/';
		$bin_dir       = $package_dir . 'bin/';
		$utils_dir     = $package_dir . 'utils/';
		$features_dir  = $package_dir . 'features/';
		$bootstrap_dir = $features_dir . 'bootstrap/';
		$steps_dir     = $features_dir . 'steps/';
		$extra_dir     = $features_dir . 'extra/';
		foreach ( array( $features_dir, $bootstrap_dir, $steps_dir, $extra_dir, $utils_dir, $bin_dir ) as $dir ) {
			if ( ! is_dir( $dir ) ) {
				Process::create( Utils\esc_cmd( 'mkdir %s', $dir ) )->run();
			}
		}

		$wp_cli_root = WP_CLI_ROOT;
		$package_root = dirname( dirname( __FILE__ ) );
		$copy_source = array(
			$wp_cli_root => array(
				'features/bootstrap/FeatureContext.php'       => $bootstrap_dir,
				'features/bootstrap/support.php'              => $bootstrap_dir,
				'php/WP_CLI/Process.php'                      => $bootstrap_dir,
				'php/utils.php'                               => $bootstrap_dir,
				'ci/behat-tags.php'                           => $utils_dir,
				'features/steps/given.php'                    => $steps_dir,
				'features/steps/when.php'                     => $steps_dir,
				'features/steps/then.php'                     => $steps_dir,
				'features/extra/no-mail.php'                  => $extra_dir,
			),
			$package_root => array(
				'.travis.yml'                                 => $package_dir,
				'templates/load-wp-cli.feature'               => $features_dir,
				'bin/install-package-tests.sh'                => $bin_dir,
			),
		);

		$files_written = array();
		foreach( $copy_source as $source => $to_copy ) {
			foreach ( $to_copy as $file => $dir ) {
				// file_get_contents() works with Phar-archived files
				$contents  = file_get_contents( $source . "/{$file}" );
				$file_path = $dir . basename( $file );

				$force = \WP_CLI\Utils\get_flag_value( $assoc_args, 'force' );
				$should_write_file = $this->prompt_if_files_will_be_overwritten( $file_path, $force );
				if ( ! $should_write_file ) {
					continue;
				}
				$files_written[] = $file_path;

				$result = Process::create( Utils\esc_cmd( 'touch %s', $file_path ) )->run();
				file_put_contents( $file_path, $contents );
				if ( 'bin/install-package-tests.sh' === $file ) {
					Process::create( Utils\esc_cmd( 'chmod +x %s', $file_path ) )->run();
				}
			}
		}

		if ( empty( $files_written ) ) {
			WP_CLI::log( 'All package test files were skipped.' );
		} else {
			WP_CLI::success( 'Created package test files.' );
		}
	}

	private static function rewrap_param_desc( $matches ) {
		$param = $matches[1];
		$desc = self::indent( "\t\t", wordwrap( $matches[2] ) );
		return "\t$param\n$desc\n\n";
	}

	private static function indent( $whitespace, $text ) {
		$lines = explode( "\n", $text );
		foreach ( $lines as &$line ) {
			$line = $whitespace . $line;
		}
		return implode( $lines, "\n" );
	}

	private function prompt_if_files_will_be_overwritten( $filename, $force ) {
		$should_write_file = true;
		if ( ! file_exists( $filename ) ) {
			return true;
		}

		WP_CLI::warning( 'File already exists' );
		WP_CLI::log( $filename );
		if ( ! $force ) {
			do {
				$answer = \cli\prompt(
					'Skip this file, or replace it with scaffolding?',
					$default = false,
					$marker = '[s/r]: '
				);
			} while ( ! in_array( $answer, array( 's', 'r' ) ) );
			$should_write_file = 'r' === $answer;
		}

		$outcome = $should_write_file ? 'Replacing' : 'Skipping';
		WP_CLI::log( $outcome . PHP_EOL );

		return $should_write_file;
	}

	private function create_files( $files_and_contents, $force ) {
		$wrote_files = array();

		foreach ( $files_and_contents as $filename => $contents ) {
			$should_write_file = $this->prompt_if_files_will_be_overwritten( $filename, $force );
			if ( ! $should_write_file ) {
				continue;
			}

			if ( ! is_dir( dirname( $filename ) ) ) {
				Process::create( Utils\esc_cmd( 'mkdir -p %s', dirname( $filename ) ) )->run();
			}

			if ( ! file_put_contents( $filename, $contents ) ) {
				WP_CLI::error( "Error creating file: $filename" );
			} elseif ( $should_write_file ) {
				$wrote_files[] = $filename;
			}
		}
		return $wrote_files;
	}

}
