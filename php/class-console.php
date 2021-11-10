<?php

use \Psy\Configuration;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Debug console code evaluation and ajax registration.
 */
class Code_Snippets_Console {


	/**
	 * Add ajax action on init.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpd_evaluate_code', array( $this, 'evaluate_wpd_console' ) );
	}


	/**
	 * Generates output array with both - original html and encoded text
	 *
	 * @param mixed $msg Output message.
	 *
	 * @return array
	 */
	protected function output_array( $msg ): array {
		return array(
			'html' => $msg,
			'text' => htmlspecialchars( $msg ),
		);
	}

	/**
	 * Evaluates PHP code
	 *
	 * @param string $input          PHP code input.
	 * @param bool   $basic_validity Check only basic validity.
	 */
	public function evaluate_code( string $input, bool $basic_validity = false ): array {
		$config = new Configuration( array(
			'configDir' => WP_CONTENT_DIR,
		) );

		$output = new Code_Snippets_ShellOutput( OutputInterface::VERBOSITY_DEBUG, true );

		$config->setOutput( $output );
		$config->setColorMode( Configuration::COLOR_MODE_AUTO );
		$config->setYolo( true );

		if ( ! $basic_validity ) {
			$config->setVerbosity( Configuration::VERBOSITY_DEBUG );
		}

		$psysh = new Code_Snippets_Shell( $config );
		$psysh->setOutput( $output );

		try {
			try {
				$psysh->addCode( $input );
				$timer = microtime( true );

				// @codingStandardsIgnoreStart
				extract( $psysh->getScopeVariablesDiff( get_defined_vars() ) );
				ob_start( array( $psysh, 'writeStdout' ), 1 );
				set_error_handler( array( $psysh, 'handleError' ) );
				// @codingStandardsIgnoreEnd

				if ( ! $basic_validity ) {
					$code = $psysh->onExecute( $psysh->flushCode() ?: $input );
				} else {
					$code = $input;
				}

				$_ = eval( $code );

				restore_error_handler();

				$psysh->setScopeVariables( get_defined_vars() );
				$psysh->writeReturnValue( $_ );

				ob_end_flush();

				if ( $output->exception ) {
					throw $output->exception;
				}

				$execution_time = microtime( true ) - $timer;

				return ( array(
					'output'         => $this->output_array( (string) $output->outputMessage ),
					'execution_time' => number_format( $execution_time, 3, '.', '' ),
				) );
			} catch ( ErrorException $e ) {
				/**
				 * Debug console? Mark as error everything - even warnings.
				 */
				if ( ! $basic_validity || in_array( $e->getSeverity(), array(
					E_ERROR,
					E_CORE_ERROR,
					E_USER_ERROR,
				), true ) ) {
					throw $e;
				}

				return array();
			}
		} catch ( Throwable $e ) {
			ob_end_flush();

			return array(
				'output' => $this->output_array( (string) $output->outputMessage ),
				'error'  => array(
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
					'file'    => $e->getFile(),
					'trace'   => $e->getTraceAsString(),
					'line'    => $e->getLine(),
				),
			);
		}
	}

	/**
	 * AJAX - Evaluates console
	 */
	public function evaluate_wpd_console() {
		$input  = stripslashes( $_POST['input'] );
		$macros = $_POST['macros'] ?? null;

		if ( ! empty( $macros ) ) {
			$input = process_snippet_macros( $input, $macros );
		}

		$output = $this->evaluate_code( $input );

		if ( ! isset( $output['error'] ) ) {
			wp_send_json_success( $output );
		} else {
			$output['status'] = 422;
			wp_send_json_error( $output );
		}
	}
}
