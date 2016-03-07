<?php

namespace WP_CLI;

use WP_CLI;
use WP_CLI\Process;
use WP_CLI\Utils;

class ScaffoldPackageCommand {

	/**
	 * Generate the files needed for a basic WP-CLI command.
	 *
	 * <dir>
	 * : Directory for the new package.
	 *
	 * [--name=<name>]
	 * : Name to appear in the composer.json.
	 *
	 * [--description=<description>]
	 * : Human-readable description for the package.
	 *
	 * [--license=<license>]
	 * : License for the package. Default: MIT.
	 *
	 * [--skip-tests]
	 * : Don't generate files for integration testing.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * @when before_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {

		list( $package_dir ) = $args;

		$defaults = array(
			'name'        => '',
			'description' => '',
			'license'     => 'MIT',
		);
		$assoc_args = array_merge( $defaults, $assoc_args );
		$force = Utils\get_flag_value( $assoc_args, 'force' );

		$template_path = dirname( dirname( __FILE__ ) ) . '/templates/';

		$files_written = $this->create_files( array(
			"{$package_dir}/.gitignore" => Utils\mustache_render( "{$template_path}/gitignore.mustache", $assoc_args ),
			"{$package_dir}/.editorconfig" => Utils\mustache_render( "{$template_path}/editorconfig.mustache", $assoc_args ),
			"{$package_dir}/wp-cli.yml" => Utils\mustache_render( "{$template_path}/wp-cli.mustache", $assoc_args ),
			"{$package_dir}/command.php" => Utils\mustache_render( "{$template_path}/command.mustache", $assoc_args ),
			"{$package_dir}/composer.json" => Utils\mustache_render( "{$template_path}/composer.mustache", $assoc_args ),
		), $force );

		if ( empty( $files_written ) ) {
			WP_CLI::log( 'All package files were skipped.' );
		} else {
			WP_CLI::success( 'Created package files.' );
		}

		if ( ! Utils\get_flag_value( $assoc_args, 'skip-tests' ) ) {
			WP_CLI::run_command( array( 'scaffold', 'package-tests', $package_dir ), array( 'force' => $force ) );
		}
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
