<?php

namespace WP_CLI;

use WP_CLI;
use WP_CLI\Utils;

/**
 * @phpstan-type ComposerConfig array{name: string, description: string, extra: array{readme: array{shields: array<string>}, commands: array<string>}, require: array<string, string>, 'require-dev': array<string, string>}
 */
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
	 * default: ^2.12
	 * ---
	 *
	 * [--require_wp_cli_tests=<version>]
	 * : Required WP-CLI testing framework version for the package.
	 * ---
	 * default: ^5.0.0
	 * ---

	 * [--skip-tests]
	 * : Don't generate files for integration testing.
	 *
	 * [--skip-readme]
	 * : Don't generate a README.md for the package.
	 *
	 * [--skip-github]
	 * : Don't generate GitHub issue and pull request templates.
	 *
	 * [--skip-install]
	 * : Don't install the package after scaffolding.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * @when before_wp_load
	 */
	public function package( $args, $assoc_args ) {

		$defaults           = [
			'dir'         => '',
			'description' => '',
			'homepage'    => '',
		];
		$assoc_args         = array_merge( $defaults, $assoc_args );
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

		if ( '~/' === substr( $package_dir, 0, 2 ) ) {
			$home = getenv( 'HOME' );
			if ( $home ) {
				$package_dir = $home . substr( $package_dir, 1 );
			}
		}

		if ( empty( $assoc_args['homepage'] ) ) {
			$assoc_args['homepage'] = 'https://github.com/' . $assoc_args['name'];
		}

		$force = Utils\get_flag_value( $assoc_args, 'force' );

		$package_root  = dirname( __DIR__ );
		$template_path = $package_root . '/templates/';
		$wp_cli_yml    = <<<'EOT'
require:
  - hello-world-command.php
EOT;

		$files_written = $this->create_files(
			[
				"{$package_dir}/.gitignore"                => file_get_contents( "{$package_root}/.gitignore" ),
				"{$package_dir}/.editorconfig"             => file_get_contents( "{$package_root}/.editorconfig" ),
				"{$package_dir}/.distignore"               => file_get_contents( "{$package_root}/.distignore" ),
				"{$package_dir}/CONTRIBUTING.md"           => file_get_contents( "{$package_root}/CONTRIBUTING.md" ),
				"{$package_dir}/wp-cli.yml"                => $wp_cli_yml,
				"{$package_dir}/hello-world-command.php"   => Utils\mustache_render( "{$template_path}/hello-world-command.mustache", $assoc_args ),
				"{$package_dir}/src/HelloWorldCommand.php" => Utils\mustache_render( "{$template_path}/HelloWorldCommand.mustache", $assoc_args ),
				"{$package_dir}/composer.json"             => Utils\mustache_render( "{$template_path}/composer.mustache", $assoc_args ),
			],
			$force
		);

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

		if ( ! Utils\get_flag_value( $assoc_args, 'skip-github' ) ) {
			WP_CLI::runcommand( "scaffold package-github {$package_dir} {$force_flag}", array( 'launch' => false ) );
		}

		if ( ! Utils\get_flag_value( $assoc_args, 'skip-install' ) ) {
			WP_CLI::runcommand( "package install {$package_dir}", array( 'launch' => false ) );
		}
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
	 * For sections, "pre", "body" and "post" are supported. Example:
	 * ```
	 * "support": {
	 *   "pre": "highlight.md",
	 *   "body": "https://raw.githubusercontent.com/runcommand/runcommand-theme/master/bin/readme-partials/support-open-source.md",
	 *   "post": "This is additional text to show after main body content."
	 * },
	 * ```
	 * In this example:
	 *
	 * * "pre" content is pulled from local highlight.md file.
	 * * "body" content is pulled from remote URL.
	 * * "post" is a string.
	 *
	 * ## OPTIONS
	 *
	 * <dir>
	 * : Directory path to an existing package to generate a readme for.
	 *
	 * [--force]
	 * : Overwrite the readme if it already exists.
	 *
	 * [--branch=<branch>]
	 * : Name of default branch of the underlying repository. Defaults to master.
	 *
	 * @when before_wp_load
	 * @subcommand package-readme
	 */
	public function package_readme( $args, $assoc_args ) {

		list( $package_dir ) = $args;

		self::check_if_valid_package_dir( $package_dir );

		/**
		 * @var null|ComposerConfig $composer_obj
		 */
		$composer_obj = json_decode( (string) file_get_contents( $package_dir . '/composer.json' ), true );
		if ( ! $composer_obj ) {
			WP_CLI::error( 'Invalid composer.json in package directory.' );
		}

		$force  = Utils\get_flag_value( $assoc_args, 'force' );
		$branch = Utils\get_flag_value( $assoc_args, 'branch', 'master' );

		$package_root  = dirname( __DIR__ );
		$template_path = $package_root . '/templates/';

		$bits        = explode( '/', $composer_obj['name'] );
		$readme_args = [
			'package_name'                  => $composer_obj['name'],
			'package_short_name'            => $bits[1],
			'package_name_border'           => str_pad( '', strlen( $composer_obj['name'] ), '=' ),
			'package_description'           => isset( $composer_obj['description'] ) ? $composer_obj['description'] : '',
			'branch'                        => $branch,
			'required_wp_cli_version'       => ! empty( $composer_obj['require']['wp-cli/wp-cli'] ) ? str_replace( [ '~', '^', '>=' ], 'v', $composer_obj['require']['wp-cli/wp-cli'] ) : 'v1.3.0',
			'shields'                       => '',
			'has_commands'                  => false,
			'wp_cli_update_to_instructions' => 'the latest stable release with `wp cli update`',
			'show_powered_by'               => isset( $composer_obj['extra']['readme']['show_powered_by'] ) ? (bool) $composer_obj['extra']['readme']['show_powered_by'] : true,
		];

		if ( isset( $composer_obj['extra']['readme']['shields'] ) ) {
			$readme_args['shields'] = implode( ' ', $composer_obj['extra']['readme']['shields'] );
		} else {
			$shields = [];
			if ( file_exists( $package_dir . '/.github/workflows/testing.yml' ) ) {
				$shields[] = "[![Testing](https://github.com/{$readme_args['package_name']}/actions/workflows/testing.yml/badge.svg)](https://github.com/{$readme_args['package_name']}/actions/workflows/testing.yml)";
			}
			if ( file_exists( $package_dir . '/.travis.yml' ) ) {
				$shields[] = "[![Build Status](https://travis-ci.org/{$readme_args['package_name']}.svg?branch={$branch})](https://travis-ci.org/{$readme_args['package_name']})";
			}
			if ( file_exists( $package_dir . '/.circleci/config.yml' ) ) {
				$shields[] = "[![CircleCI](https://circleci.com/gh/{$readme_args['package_name']}/tree/{$branch}.svg?style=svg)](https://circleci.com/gh/{$readme_args['package_name']}/tree/{$branch})";
			}
			if ( file_exists( $package_dir . '/codecov.yml' ) ) {
				$shields[] = "[![Code Coverage](https://codecov.io/gh/{$readme_args['package_name']}/branch/{$branch}/graph/badge.svg)](https://codecov.io/gh/{$readme_args['package_name']}/tree/{$branch})";
			}

			if ( count( $shields ) ) {
				$readme_args['shields'] = implode( ' ', $shields );
			}
		}

		$readme_args['wp_cli_requires_instructions'] = "requires WP-CLI {$readme_args['required_wp_cli_version']} or greater";
		if ( '*' === $readme_args['required_wp_cli_version'] ) {
			$readme_args['wp_cli_requires_instructions'] = "requires WP-CLI's latest stable release";
		}

		if ( false !== stripos( $readme_args['required_wp_cli_version'], 'alpha' ) ) {
			$readme_args['wp_cli_update_to_instructions'] = 'the latest nightly release with `wp cli update --nightly`';
		}

		if ( ! empty( $composer_obj['extra']['commands'] ) ) {
			$readme_args['commands'] = [];
			$cmd_dump                = WP_CLI::runcommand(
				'cli cmd-dump',
				[
					'launch' => false,
					'return' => true,
					'parse'  => 'json',
				]
			);
			foreach ( $composer_obj['extra']['commands'] as $command ) {
				$bits           = explode( ' ', $command );
				$parent_command = $cmd_dump;
				do {
					$cmd_bit = array_shift( $bits );
					$found   = false;
					foreach ( $parent_command['subcommands'] as $subcommand ) {
						if ( $subcommand['name'] === $cmd_bit ) {
							$parent_command = $subcommand;
							$found          = true;
							break;
						}
					}
					if ( ! $found ) {
						$parent_command = false;
					}
				} while ( $parent_command && $bits );

				if ( empty( $parent_command ) ) {
					WP_CLI::error( 'Missing one or more commands defined in composer.json -> extra -> commands.' );
				}

				$longdesc = isset( $parent_command['longdesc'] ) ? $parent_command['longdesc'] : '';
				$longdesc = (string) preg_replace( '/## GLOBAL PARAMETERS(.+)/s', '', $longdesc );
				$longdesc = (string) preg_replace( '/##\s(.+)/', '**$1**', $longdesc );

				// definition lists
				$longdesc = preg_replace_callback( '/([^\n]+)\n: (.+?)(\n\n|$)/s', [ __CLASS__, 'rewrap_param_desc' ], $longdesc );

				$command_data = [
					'name'      => "wp {$command}",
					'shortdesc' => isset( $parent_command['description'] ) ? $parent_command['description'] : '',
					'synopsis'  => "wp {$command}" . ( empty( $parent_command['subcommands'] ) ? ( isset( $parent_command['synopsis'] ) ? " {$parent_command['synopsis']}" : '' ) : '' ),
					'longdesc'  => $longdesc,
				];

				// Add alias if present.
				if ( ! empty( $parent_command['alias'] ) ) {
					$command_data['alias'] = $parent_command['alias'];
				}

				$readme_args['commands'][] = $command_data;
			}
			$readme_args['has_commands']          = true;
			$readme_args['has_multiple_commands'] = count( $readme_args['commands'] ) > 1;
		}

		if ( isset( $composer_obj['extra']['readme']['sections'] ) ) {
			$readme_section_headings = $composer_obj['extra']['readme']['sections'];
		} else {
			$readme_section_headings = [
				'Using',
				'Installing',
				'Contributing',
				'Support',
			];
		}

		$readme_sections = [];
		foreach ( $readme_section_headings as $section_heading ) {
			$key                     = strtolower( preg_replace( '#[^\da-z-_]#i', '', $section_heading ) );
			$readme_sections[ $key ] = [
				'heading' => $section_heading,
			];
		}
		$bundled = ! empty( $composer_obj['extra']['bundled'] );
		foreach ( [ 'using', 'installing', 'contributing', 'support' ] as $key ) {
			if ( isset( $readme_sections[ $key ] ) ) {
				$file = dirname( __DIR__ ) . '/templates/readme-' . $key . '.mustache';
				if ( $bundled
					&& file_exists( dirname( __DIR__ ) . '/templates/readme-' . $key . '-bundled.mustache' ) ) {
					$file = dirname( __DIR__ ) . '/templates/readme-' . $key . '-bundled.mustache';
				}
				$readme_sections[ $key ]['body'] = $file;
			}
		}

		$readme_sections['package_description'] = [
			'body' => isset( $composer_obj['description'] ) ? $composer_obj['description'] : '',
		];

		$readme_args['quick_links'] = '';
		foreach ( $readme_sections as $key => $section ) {
			if ( ! empty( $section['heading'] ) ) {
				$readme_args['quick_links'] .= '[' . $section['heading'] . '](#' . $key . ') | ';
			}
		}
		if ( ! empty( $readme_args['quick_links'] ) ) {
			$readme_args['quick_links'] = 'Quick links: ' . rtrim( $readme_args['quick_links'], '| ' );
		}

		$readme_args['sections'] = [];
		$ext_regex               = '#\.(md|mustache)$#i';
		foreach ( $readme_sections as $section => $section_args ) {
			$value = [];
			foreach ( [ 'pre', 'body', 'post' ] as $k ) {
				$v = '';
				if ( isset( $composer_obj['extra']['readme'][ $section ][ $k ] ) ) {
					$v = $composer_obj['extra']['readme'][ $section ][ $k ];
					if ( filter_var( $v, FILTER_VALIDATE_URL ) === $v ) {
						$response = Utils\http_request( 'GET', $v );

						// @phpstan-ignore class.notFound
						$v = $response->body;
					} elseif ( preg_match( $ext_regex, $v ) ) {
						$v = $package_dir . '/' . $v;
					}
				} elseif ( isset( $section_args[ $k ] ) ) {
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
				$readme_args['sections'][] = [
					'heading' => $section_args['heading'],
					'body'    => $value,
				];
			}
		}

		$files_written = $this->create_files(
			[
				"{$package_dir}/README.md" => Utils\mustache_render( "{$template_path}/readme.mustache", $readme_args ),
			],
			$force
		);

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
	 * * `.github/settings.yml` - Configuration file for the [Probot settings app](https://probot.github.io/apps/settings/).
	 *
	 * ## OPTIONS
	 *
	 * <dir>
	 * : Directory path to an existing package to generate GitHub configuration for.
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
		} elseif ( is_dir( $package_dir ) ) {
			$package_dir = rtrim( $package_dir, '/' );
		}

		self::check_if_valid_package_dir( $package_dir );

		$force         = Utils\get_flag_value( $assoc_args, 'force' );
		$template_path = dirname( __DIR__ ) . '/templates';

		/**
		 * @var ComposerConfig $composer_obj
		 */
		$composer_obj  = json_decode( (string) file_get_contents( $package_dir . '/composer.json' ), true );
		$settings_vars = [
			'has_description' => false,
			'description'     => '',
			'has_labels'      => true,
			'labels'          => [
				[
					'name'  => 'bug',
					'color' => 'fc2929',
				],
				[
					'name'  => 'scope:documentation',
					'color' => '0e8a16',
				],
				[
					'name'  => 'scope:testing',
					'color' => '5319e7',
				],
				[
					'name'  => 'good-first-issue',
					'color' => 'eb6420',
				],
				[
					'name'  => 'help-wanted',
					'color' => '159818',
				],
				[
					'name'  => 'maybelater',
					'color' => 'c2e0c6',
				],
				[
					'name'  => 'state:unconfirmed',
					'color' => 'bfe5bf',
				],
				[
					'name'  => 'state:unsupported',
					'color' => 'bfe5bf',
				],
				[
					'name'  => 'wontfix',
					'color' => 'c2e0c6',
				],
			],
		];
		if ( ! empty( $composer_obj['description'] ) ) {
			$settings_vars['description']     = $composer_obj['description'];
			$settings_vars['has_description'] = true;
		}
		if ( ! empty( $composer_obj['extra']['commands'] ) ) {
			foreach ( $composer_obj['extra']['commands'] as $cmd ) {
				$settings_vars['labels'][] = [
					'name'  => 'command:' . str_replace( ' ', '-', $cmd ),
					'color' => 'c5def5',
				];
			}
		}
		$create_files  = [
			"{$package_dir}/.github/ISSUE_TEMPLATE"        => Utils\mustache_render( "{$template_path}/github-issue-template.mustache" ),
			"{$package_dir}/.github/PULL_REQUEST_TEMPLATE" => Utils\mustache_render( "{$template_path}/github-pull-request-template.mustache" ),
			"{$package_dir}/.github/settings.yml"          => Utils\mustache_render( "{$template_path}/github-settings.mustache", $settings_vars ),
		];
		$files_written = $this->create_files( $create_files, $force );
		if ( empty( $files_written ) ) {
			WP_CLI::log( 'Package GitHub configuration generation skipped.' );
		} else {
			WP_CLI::success( 'Created package GitHub configuration.' );
		}
	}

	/**
	 * Generate files for writing Behat tests for your command.
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
	 * For Travis CI, specially-named files in the package directory can be
	 * used to modify the generated `.travis.yml`, where `<tag>` is one of
	 * 'cache', 'env', 'matrix', 'before_install', 'install', 'before_script', 'script':
	 * * `travis-<tag>.yml` - contents used for `<tag>:` (if present following ignored)
	 * * `travis-<tag>-append.yml` - contents appended to generated `<tag>:`
	 *
	 * You can also append to the generated `.travis.yml` with the file:
	 * * `travis-append.yml` - contents appended to generated `.travis.yml`
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
	 * : Directory path to an existing package to generate tests for.
	 *
	 * [--ci=<provider>]
	 * : Create a configuration file for a specific CI provider.
	 * ---
	 * default: travis
	 * options:
	 *   - travis
	 *   - circle
	 *   - github
	 * ---
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate files for writing Behat tests.
	 *     $ wp scaffold package-tests /path/to/command/dir/
	 *     Success: Created package test files.
	 *
	 * @when       before_wp_load
	 * @subcommand package-tests
	 */
	public function package_tests( $args, $assoc_args ) {
		list( $package_dir ) = $args;

		if ( is_file( $package_dir ) ) {
			$package_dir = dirname( $package_dir );
		} elseif ( is_dir( $package_dir ) ) {
			$package_dir = rtrim( $package_dir, '/' );
		}

		self::check_if_valid_package_dir( $package_dir );

		$package_dir .= '/';
		$features_dir = $package_dir . 'features/';
		if ( ! is_dir( $features_dir ) ) {
			Process::create( Utils\esc_cmd( 'mkdir %s', $features_dir ) )->run();
		}

		$package_root = dirname( __DIR__ );

		// Only create a sample feature file when none exist
		if ( ! glob( $features_dir . '/*.feature' ) ) {
			$copy_source[ $package_root ]['templates/load-wp-cli.feature'] = $features_dir;
		}

		$copy_source[ $package_root ]['behat.yml'] = $package_dir;

		$travis_tags           = [ 'cache', 'env', 'matrix', 'before_install', 'install', 'before_script', 'script' ];
		$travis_tag_overwrites = [];
		$travis_tag_appends    = [];
		$travis_append         = '';
		if ( 'travis' === $assoc_args['ci'] ) {
			$copy_source[ $package_root ]['templates/.travis.yml'] = $package_dir;

			// Allow a package to overwrite or append to Travis tags.
			foreach ( $travis_tags as $travis_tag ) {
				if ( file_exists( $package_dir . 'travis-' . $travis_tag . '.yml' ) ) {
					$travis_tag_overwrites[ $travis_tag ] = file_get_contents( $package_dir . 'travis-' . $travis_tag . '.yml' );
				} elseif ( file_exists( $package_dir . 'travis-' . $travis_tag . '-append.yml' ) ) {
					$travis_tag_appends[ $travis_tag ] = file_get_contents( $package_dir . 'travis-' . $travis_tag . '-append.yml' );
				}
			}

			// Allow a package to append to Travis.
			if ( file_exists( $package_dir . 'travis-append.yml' ) ) {
				$travis_append = file_get_contents( $package_dir . 'travis-append.yml' );
			}
		} elseif ( 'circle' === $assoc_args['ci'] ) {
			$copy_source[ $package_root ]['.circleci/config.yml'] = $package_dir . '.circleci/';
		} elseif ( 'github' === $assoc_args['ci'] ) {
			$copy_source[ $package_root ]['templates/testing.yml'] = $package_dir . '.github/workflows/';
		}

		$files_written = [];
		foreach ( $copy_source as $source => $to_copy ) {
			foreach ( $to_copy as $file => $dir ) {
				// file_get_contents() works with Phar-archived files
				$contents  = (string) file_get_contents( $source . "/{$file}" );
				$file_path = $dir . basename( $file );

				$force             = Utils\get_flag_value( $assoc_args, 'force' );
				$should_write_file = $this->prompt_if_files_will_be_overwritten( $file_path, $force );
				if ( ! $should_write_file ) {
					continue;
				}
				$files_written[] = $file_path;

				if ( ! is_dir( dirname( $file_path ) ) ) {
					Process::create( Utils\esc_cmd( 'mkdir -p %s', dirname( $file_path ) ) )->run();
				}

				Process::create( Utils\esc_cmd( 'touch %s', $file_path ) )->run();
				file_put_contents( $file_path, $contents );
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
		$desc  = self::indent( "\t\t", $matches[2] );
		return "\t$param\n$desc\n\n";
	}

	private static function indent( $whitespace, $text ) {
		$lines = explode( "\n", $text );
		foreach ( $lines as &$line ) {
			$line = $whitespace . $line;
		}
		return implode( "\n", $lines );
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
				$answer      = \cli\prompt(
					'Skip this file, or replace it with scaffolding?',
					$default = false,
					$marker  = '[s/r]: '
				);
			} while ( ! in_array( $answer, [ 's', 'r' ], true ) );
			$should_write_file = 'r' === $answer;
		}

		$outcome = $should_write_file ? 'Replacing' : 'Skipping';
		WP_CLI::log( $outcome . PHP_EOL );

		return $should_write_file;
	}

	private function create_files( $files_and_contents, $force ) {
		$wrote_files = [];

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

	private static function check_if_valid_package_dir( $package_dir ) {
		if ( ! is_dir( $package_dir ) ) {
			WP_CLI::error( 'Directory does not exist.' );
		}

		if ( ! file_exists( $package_dir . '/composer.json' ) ) {
			WP_CLI::error( 'Invalid package directory. composer.json file must be present.' );
		}
	}
}
