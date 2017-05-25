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
	 * - .gitignore, .editorconfig, and .distignore
	 * - README.md (via wp scaffold package-readme)
	 * - Test harness (via wp scaffold package-tests)
	 *
	 * Unless specified with `--dir=<dir>`, the command package is placed in the
	 * WP-CLI `packages/local/` directory.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Name for the new package. Expects <author>/<package> (e.g. 'wp-cli/scaffold-package').
	 *
	 * [--description=<description>]
	 * : Human-readable description for the package.
	 *
	 * [--homepage=<homepage>]
	 * : Homepage for the package. Defaults to 'https://github.com/<name>'
	 *
	 * [--dir=<dir>]
	 * : Specify a destination directory for the command. Defaults to WP-CLI's `packages/local/` directory.
	 *
	 * [--license=<license>]
	 * : License for the package.
	 * ---
	 * default: MIT
	 * ---
	 *
	 * [--require_wp_cli=<version>]
	 * : Required WP-CLI version for the package.
	 * ---
	 * default: ^1.1.0
	 * ---
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
			'homepage'     => '',
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
			$package_dir = WP_CLI::get_runner()->get_packages_dir_path() . 'local/' . $assoc_args['name'];
		}

		if ( empty( $assoc_args['homepage'] ) ) {
			$assoc_args['homepage'] = 'https://github.com/' . $assoc_args['name'];
		}

		$force = Utils\get_flag_value( $assoc_args, 'force' );

		$package_root = dirname( dirname( __FILE__ ) );
		$template_path = $package_root . '/templates/';
		$wp_cli_yml = <<<EOT
require:
  - command.php
EOT;

		$files_written = $this->create_files( array(
			"{$package_dir}/.gitignore"     => file_get_contents( "{$package_root}/.gitignore" ),
			"{$package_dir}/.editorconfig"  => file_get_contents( "{$package_root}/.editorconfig" ),
			"{$package_dir}/.distignore"    => file_get_contents( "{$package_root}/.distignore" ),
			"{$package_dir}/wp-cli.yml"     => $wp_cli_yml,
			"{$package_dir}/command.php"    => Utils\mustache_render( "{$template_path}/command.mustache", $assoc_args ),
			"{$package_dir}/composer.json"  => Utils\mustache_render( "{$template_path}/composer.mustache", $assoc_args ),
		), $force );

		if ( empty( $files_written ) ) {
			WP_CLI::log( 'All package files were skipped.' );
		} else {
			WP_CLI::success( "Created package files in {$package_dir}" );
		}

		$force_flag = $force ? '--force' : '';
		if ( ! Utils\get_flag_value( $assoc_args, 'skip-tests' ) ) {
			WP_CLI::runcommand( "scaffold package-tests {$package_dir} {$force_flag}", array( 'launch' => false ) );
		}

		if ( ! Utils\get_flag_value( $assoc_args, 'skip-readme' ) ) {
			WP_CLI::runcommand( "scaffold package-readme {$package_dir} {$force_flag}", array( 'launch' => false ) );
		}

		WP_CLI::runcommand( "package install {$package_dir}", array( 'launch' => false ) );
	}

	/**
	 * Generate a README.md for your command.
	 *
	 * Creates a README.md with Using, Installing, and Contributing instructions
	 * based on the composer.json file for your WP-CLI package. Run this command
	 * at the beginning of your project, and then every time your usage docs
	 * change.
	 *
	 * These command-specific docs are generated based composer.json -> 'extra'
	 * -> 'commands'. For instance, this package's composer.json includes:
	 *
	 * ```
	 * {
	 *   "name": "wp-cli/scaffold-package-command",
	 *    // [...]
	 *    "extra": {
	 *        "commands": [
	 *            "scaffold package",
	 *            "scaffold package-tests",
	 *            "scaffold package-readme"
	 *        ]
	 *    }
	 * }
	 * ```
	 *
	 * You can also customize the rendering of README.md generally with
	 * composer.json -> 'extra' -> 'readme'. For example, runcommand/hook's
	 * composer.json includes:
	 *
	 * ```
	 * {
	 *     "extra": {
	 *         "commands": [
	 *             "hook"
	 *         ],
	 *         "readme": {
	 *             "shields": [
	 *                 "[![Build Status](https://travis-ci.org/runcommand/reset-password.svg?branch=master)](https://travis-ci.org/runcommand/reset-password)"
	 *             ],
	 *             "sections": [
	 *                 "Using",
	 *                 "Installing",
	 *                 "Support"
	 *             ],
	 *             "support": {
	 *                 "body": "https://raw.githubusercontent.com/runcommand/runcommand-theme/master/bin/readme-partials/support-open-source.md"
	 *             },
	 *             "show_powered_by": false
	 *         }
	 *     }
	 * }
	 * ```
	 *
	 * In this example:
	 *
	 * * "shields" supports arbitrary images as shields to display.
	 * * "sections" permits defining arbitrary sections (instead of default Using, Installing and Contributing).
	 * * "support" -> "body" uses a remote Markdown file as the section contents. This can also be a local file path, or a string.
	 * * "show_powered_by" shows or hides the Powered By mention at the end of the readme.
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
			'required_wp_cli_version' => ! empty( $composer_obj['require']['wp-cli/wp-cli'] ) ? str_replace( array( '~', '^', '>=' ), 'v', $composer_obj['require']['wp-cli/wp-cli'] ) : 'v0.23.0',
			'shields'             => '',
			'has_commands'        => false,
			'wp_cli_update_to_instructions' => 'the latest stable release with `wp cli update`',
			'show_powered_by'     => isset( $composer_obj['extra']['readme']['show_powered_by'] ) ? (bool) $composer_obj['extra']['readme']['show_powered_by'] : true,
		);

		if ( isset( $composer_obj['extra']['readme']['shields'] ) ) {
			$readme_args['shields'] = implode( ' ', $composer_obj['extra']['readme']['shields'] );
		} else {
			$shields = array();
			if ( file_exists( $package_dir . '/.travis.yml' ) ) {
				$shields[] = "[![Build Status](https://travis-ci.org/{$readme_args['package_name']}.svg?branch=master)](https://travis-ci.org/{$readme_args['package_name']})";
			}
			if ( file_exists( $package_dir . '/circle.yml' ) ) {
				$shields[] = "[![CircleCI](https://circleci.com/gh/{$readme_args['package_name']}/tree/master.svg?style=svg)](https://circleci.com/gh/{$readme_args['package_name']}/tree/master)";
			}

			if ( count( $shields ) ) {
				$readme_args['shields'] = implode( ' ', $shields );
			}
		}

		if ( false !== stripos( $readme_args['required_wp_cli_version'], 'alpha' ) ) {
			$readme_args['wp_cli_update_to_instructions'] = 'the latest nightly release with `wp cli update --nightly`';
		}

		if ( ! empty( $composer_obj['extra']['commands'] ) ) {
			$readme_args['commands'] = array();
			$cmd_dump = WP_CLI::runcommand( 'cli cmd-dump', array( 'launch' => false, 'return' => true, 'parse' => 'json' ) );
			foreach( $composer_obj['extra']['commands'] as $command ) {
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

				if ( empty( $parent_command ) ) {
					WP_CLI::error( 'Missing one or more commands defined in composer.json -> extras -> commands.' );
				}

				$longdesc = preg_replace( '/## GLOBAL PARAMETERS(.+)/s', '', $parent_command['longdesc'] );
				$longdesc = preg_replace( '/##\s(.+)/', '**$1**', $longdesc );

				// definition lists
				$longdesc = preg_replace_callback( '/([^\n]+)\n: (.+?)(\n\n|$)/s', array( __CLASS__, 'rewrap_param_desc' ), $longdesc );

				$readme_args['commands'][] = array(
					'name' => "wp {$command}",
					'shortdesc' => $parent_command['description'],
					'synopsis' => "wp {$command}" . ( empty( $parent_command['subcommands'] ) ? " {$parent_command['synopsis']}" : "" ),
					'longdesc' => $longdesc,
				);
			}
			$readme_args['has_commands'] = true;
			$readme_args['has_multiple_commands'] = count( $readme_args['commands'] ) > 1 ? true : false;
		}

		if ( isset( $composer_obj['extra']['readme']['sections'] ) ) {
			$readme_section_headings = $composer_obj['extra']['readme']['sections'];
		} else {
			$readme_section_headings = array(
				'Using',
				'Installing',
				'Contributing',
			);
		}

		$readme_sections = array();
		foreach( $readme_section_headings as $section_heading ) {
			$key = strtolower( preg_replace( '#[^\da-z-_]#i', '', $section_heading ) );
			$readme_sections[ $key ] = array(
				'heading'      => $section_heading,
			);
		}
		foreach( array( 'using', 'installing', 'contributing' ) as $key ) {
			if ( isset( $readme_sections[ $key ] ) ) {
				$readme_sections[ $key ]['body'] = dirname( dirname( __FILE__ ) ) . '/templates/readme-' . $key . '.mustache';
			}
		}

		$readme_sections['package_description'] = array(
			'body' => $composer_obj['description'],
		);

		$readme_args['quick_links'] = '';
		foreach( $readme_sections as $key => $section ) {
			if ( ! empty( $section['heading'] ) ) {
				$readme_args['quick_links'] .= '[' . $section['heading'] . '](#' . $key . ') | ';
			}
		}
		if ( ! empty( $readme_args['quick_links'] ) ) {
			$readme_args['quick_links'] = 'Quick links: ' . rtrim( $readme_args['quick_links'], '| ' );
		}

		$readme_args['sections'] = array();
		$ext_regex = '#\.(md|mustache)$#i';
		foreach( $readme_sections as $section => $section_args ) {
			$value = array();
			foreach( array( 'pre', 'body', 'post' ) as $k ) {
				$v = '';
				if ( isset( $composer_obj['extra']['readme'][ $section ][ $k ] ) ) {
					$v = $composer_obj['extra']['readme'][ $section][ $k ];
					if ( false !== stripos( $v, '://' ) ) {
						$response = Utils\http_request( 'GET', $v );
						$v = $response->body;
					} else if ( preg_match( $ext_regex, $v ) ) {
						$v = $package_dir . '/' . $v;
					}
				} else if ( isset( $section_args[ $k ] ) ) {
					$v = $section_args[ $k ];
				}
				if ( $v ) {
					if ( preg_match( $ext_regex, $v ) ) {
						$v = Utils\mustache_render( $v, $readme_args );
					}
					$value[] = trim( $v );
				}
			}
			$value = trim( implode( PHP_EOL . PHP_EOL, $value ) );
			if ( 'package_description' === $section ) {
				$readme_args['package_description'] = $value;
			} else {
				$readme_args['sections'][] = array(
					'heading'      => $section_args['heading'],
					'body'         => $value,
				);
			}
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
	 * Generate GitHub configuration files for your command.
	 *
	 * Creates a variety of files to better manage your project on GitHub. These
	 * files include:
	 *
	 * * `.github/ISSUE_TEMPLATE` - Text displayed when a user opens a new issue.
	 * * `.github/PULL_REQUEST_TEMPLATE` - Text displayed when a user submits a pull request.
	 *
	 * ## OPTIONS
	 *
	 * <dir>
	 * : The package directory to generate GitHub configuration for.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * @when       before_wp_load
	 * @subcommand package-github
	 */
	public function package_github( $args, $assoc_args ) {
		list( $package_dir ) = $args;

		if ( is_file( $package_dir ) ) {
			$package_dir = dirname( $package_dir );
		} else if ( is_dir( $package_dir ) ) {
			$package_dir = rtrim( $package_dir, '/' );
		}

		if ( ! is_dir( $package_dir ) || ! file_exists( $package_dir . '/composer.json' ) ) {
			WP_CLI::error( "Invalid package directory. composer.json file must be present." );
		}
		$force = Utils\get_flag_value( $assoc_args, 'force' );
		$template_path = dirname( dirname( __FILE__ ) ) . '/templates';

		$create_files = array(
			"{$package_dir}/.github/ISSUE_TEMPLATE" => Utils\mustache_render( "{$template_path}/github-issue-template.mustache" ),
			"{$package_dir}/.github/PULL_REQUEST_TEMPLATE" => Utils\mustache_render( "{$template_path}/github-pull-request-template.mustache" ),
		);
		$files_written = $this->create_files( $create_files, $force );
		if ( empty( $files_written ) ) {
			WP_CLI::log( 'Package GitHub configuration generation skipped.' );
		} else {
			WP_CLI::success( 'Created package GitHub configuration.' );
		}
	}

	/**
	 * Generate files needed for writing Behat tests for your command.
	 *
	 * WP-CLI makes use of a Behat-based testing framework, which you should use
	 * too. This command generates all of the files you need. Functional tests
	 * are an integral ingredient of high-quality, maintainable commands.
	 * Behat is a great choice as a testing framework because:
	 *
	 * * It’s easy to write new tests, which means they’ll actually get written.
	 * * The tests interface with your command in the same manner as your users
	 * interface with your command, and they describe how the command is
	 * expected to work in human-readable terms.
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
	 * Functional tests typically follow this pattern:
	 *
	 * * **Given** some background,
	 * * **When** a user performs a specific action,
	 * * **Then** the end result should be X (and Y and Z).
	 *
	 * View all defined Behat steps available for use with `behat -dl`:
	 *
	 * ```
	 * Given /^an empty directory$/
	 * Given /^an empty cache/
	 * Given /^an? ([^\s]+) file:$/
	 * Given /^"([^"]+)" replaced with "([^"]+)" in the ([^\s]+) file$/
	 * ```
	 *
	 * The files generated by this command include:
	 *
	 * * `.travis.yml` is the configuration file for Travis CI.
	 * * `bin/install-package-tests.sh` will configure your environment to run
	 * the tests.
	 * * `bin/test.sh` is a test runner that respects contextual Behat tags.
	 * * `features/load-wp-cli.feature` is a basic test to confirm WP-CLI can
	 * load.
	 * * `features/bootstrap`, `features/steps`, `features/extra` are Behat
	 * configuration files.
	 *
	 * After running `bin/install-package-tests.sh`, you can run the tests with
	 * `./vendor/bin/behat`. If you find yourself using Behat on a number of
	 * projects and don't want to install a copy with each one, you can
	 * `composer global require behat/behat` to install Behat globally on your
	 * machine. Make sure `~/.composer/vendor/bin` has also been added to your
	 * `$PATH`. Once you've done so, you can run the tests for a project by
	 * calling `behat`.
	 *
	 * ## ENVIRONMENT
	 *
	 * The `features/bootstrap/FeatureContext.php` file expects the
	 * WP_CLI_BIN_DIR environment variable.
	 *
	 * WP-CLI Behat framework uses Behat ~2.5, which is installed with Composer.
	 *
	 * ## OPTIONS
	 *
	 * <dir>
	 * : The package directory to generate tests for.
	 *
	 * [--ci=<provider>]
	 * : Create a configuration file for a specific CI provider.
	 * ---
	 * default: travis
	 * options:
	 *   - travis
	 *   - circle
	 * ---
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
				'php/WP_CLI/ProcessRun.php'                   => $bootstrap_dir,
				'php/utils.php'                               => $bootstrap_dir,
				'ci/behat-tags.php'                           => $utils_dir,
				'features/steps/given.php'                    => $steps_dir,
				'features/steps/when.php'                     => $steps_dir,
				'features/steps/then.php'                     => $steps_dir,
				'features/extra/no-mail.php'                  => $extra_dir,
			),
			$package_root => array(
				'bin/install-package-tests.sh'                => $bin_dir,
				'bin/test.sh'                                 => $bin_dir,
			),
		);

		// Only create a sample feature file when none exist
		if ( ! glob( $features_dir . '/*.feature' ) ) {
			$copy_source[ $package_root ]['templates/load-wp-cli.feature'] = $features_dir;
		}

		if ( 'travis' === $assoc_args['ci'] ) {
			$copy_source[ $package_root ]['.travis.yml'] = $package_dir;
		} else if ( 'circle' === $assoc_args['ci'] ) {
			$copy_source[ $package_root ]['circle.yml'] = $package_dir;
		}

		$files_written = array();
		foreach( $copy_source as $source => $to_copy ) {
			foreach ( $to_copy as $file => $dir ) {
				if ( 'php/WP_CLI/ProcessRun.php' === $file && ! file_exists( $source . "/{$file}" ) ) {
					continue;
				}
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
		$desc = self::indent( "\t\t", $matches[2] );
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
