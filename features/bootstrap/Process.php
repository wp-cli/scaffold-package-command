<?php

namespace WP_CLI;

/**
 * Run a system process, and learn what happened.
 */
class Process {

	/**
	 * @param string $command Command to execute.
	 * @param string $cwd Directory to execute the command in.
	 * @param array $env Environment variables to set when running the command.
	 */
	public static function create( $command, $cwd = null, $env = array() ) {
		$proc = new self;

		$proc->command = $command;
		$proc->cwd = $cwd;
		$proc->env = $env;

		return $proc;
	}

	private $command, $cwd, $env;

	private function __construct() {}

	/**
	 * Run the command.
	 *
	 * @return ProcessRun
	 */
	public function run() {
		$cwd = $this->cwd;

		$descriptors = array(
			0 => STDIN,
			1 => array( 'pipe', 'w' ),
			2 => array( 'pipe', 'w' ),
		);

		// Convert environment variables.
		$command = preg_replace_callback( '/\$([A-Z_]+)/', function( $matches ){
			$cmd = $matches[0];
			foreach ( array_slice( $matches, 1 ) as $key ) {
				$cmd = str_replace( '$' . $key, escapeshellarg( getenv( $key ) ), $cmd );
			}
			return $cmd;
		}, $this->command );

		$proc = proc_open( $command, $descriptors, $pipes, $cwd, $this->env );

		$stdout = stream_get_contents( $pipes[1] );
		fclose( $pipes[1] );

		$stderr = stream_get_contents( $pipes[2] );
		fclose( $pipes[2] );

		return new ProcessRun( array(
			'stdout' => $stdout,
			'stderr' => $stderr,
			'return_code' => proc_close( $proc ),
			'command' => $this->command,
			'cwd' => $cwd,
			'env' => $this->env
		) );
	}

	/**
	 * Run the command, but throw an Exception on error.
	 *
	 * @return ProcessRun
	 */
	public function run_check() {
		$r = $this->run();

		if ( $r->return_code || !empty( $r->STDERR ) ) {
			throw new \RuntimeException( $r );
		}

		return $r;
	}
}
