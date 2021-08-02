<?php
use WPConsole\Core\Console\Psy\Output\ShellOutput;
use WPConsole\Core\Console\Psy\Shell;
#use Psy;
class Code_Snippets_API {
	const WPD_URL = 'https://wpdistro.com/wp-json/wp/v2/';

	function __construct()
	{
		add_action( 'wp_ajax_nopriv_evaluatewpd', array($this, 'evaluate_wpd_console') );
		add_action( 'wp_ajax_evaluatewpd', array($this, 'evaluate_wpd_console') );
		#add_action( 'rest_api_init', array($this, 'register_wpd_endpoints') );
	}

	function register_wpd_endpoints() {
		register_rest_route( 'wpd', '/evaluate', array(
		  'methods' => WP_REST_Server::READABLE,
		  'callback' => array($this, 'evaluate_wpd_console') ),
		);
	}
	
	
	function evaluate_wpd_console(){
		try {
			$timer = microtime( true );
			$input = base64_decode($_POST['input']);
	
			$config = new \Psy\Configuration( [
				'configDir' => WP_CONTENT_DIR,
			] );
	
			$output = new \WPConsole\Core\Console\Psy\Output\ShellOutput(  \WPConsole\Core\Console\Psy\Output\ShellOutput::VERBOSITY_NORMAL, true );
	
			$config->setOutput( $output );
			$config->setColorMode( \Psy\Configuration::COLOR_MODE_DISABLED );
	
			$psysh = new  WPConsole\Core\Console\Psy\Shell( $config );
	
			$psysh->setOutput( $output );
	
			$psysh->addCode( $input );
	
			extract( $psysh->getScopeVariablesDiff( get_defined_vars() ) );
	
			ob_start( [ $psysh, 'writeStdout' ], 1 );
	
			set_error_handler( [ $psysh, 'handleError' ] );
	
			$_ = eval( $psysh->onExecute( $psysh->flushCode() ?: \Psy\ExecutionClosure::NOOP_INPUT ) );
	
			restore_error_handler();
	
			$psysh->setScopeVariables( get_defined_vars() );
			$psysh->writeReturnValue( $_ );
	
			ob_end_flush();
	
			if ( $output->exception ) {
				throw $output->exception;
			}
	
			$execution_time = microtime( true ) - $timer;
	
			$data = [
				'output'         => $output->outputMessage,
				'dump'           => $wp_console_dump,
				'execution_time' => number_format( $execution_time, 3, '.', '' ),
			];
			wp_send_json($data);
			//echo rest_ensure_response( $data );
			wp_die();
		} catch ( Throwable $e ) {
			ob_end_flush();
			wp_send_json_error( [
				'message' => $e->getMessage(),
				'input'  => $request['input'],
				'status' => 422,
				'trace'  => $e->getTraceAsString(),
			]);
			wp_die();
		}
	}
	protected function get_http_args( $args ): array {
		global $wp_version;

		return wp_parse_args( $args, array(
			'timeout'    => 15,
			'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
		) );
	}

	protected function detect_error( $request ) {
		if ( is_wp_error( $request ) ) {
			return new WP_Error(
				'wpd_api_failed',
				__( 'An unexpected error occurred. Failed to fetch WPDistro API.', 'code-snippets' ),
				$request->get_error_message()
			);
		} else {
			return $request;
		}
	}

	protected function get_request_url( $action ) {
		return wp_http_validate_url( $action ) ? $action : self::WPD_URL . $action;
	}

	/**
	 * @param $action
	 * @param $headers
	 *
	 * @return array|WP_Error
	 */
	public function get_request( $action, $headers ) {
		$http_args = $this->get_http_args( array(
			'headers' => $headers,
		) );
		$url       = $this->get_request_url( $action );
		$request   = wp_remote_get( $url, $http_args );

		return $this->detect_error( $request );
	}

	/**
	 * @param $action
	 * @param $body
	 * @param $headers
	 *
	 * @return array|WP_Error
	 */
	public function post_request( $action, $body, $headers ) {
		$headers   = wp_parse_args( $headers, array(
			'Content-Type' => 'application/json; charset=utf-8',
		) );
		$http_args = $this->get_http_args( array(
			'headers' => $headers,
			'body'    => $body,
		) );
		$url       = $this->get_request_url( $action );
		$request   = wp_remote_post( $url, $http_args );

		return $this->detect_error( $request );
	}
}

