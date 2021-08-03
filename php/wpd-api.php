<?php

if ( ! class_exists( 'WPD_Snippet' ) ) {
	require_once dirname( __FILE__ ) . '/wpd_snippet.php';
}
use WPConsole\Core\Console\Psy\Output\ShellOutput;
use WPConsole\Core\Console\Psy\Shell;

/**
 * @param string $action Endpoint (or whole URL) to request
 * @param string $method Request method
 * @param array $args Additional args
 *
 * @return false|array
 */
function wpd_request( string $action, string $method = 'GET', $args = array() ) {
	$default_args = array(
		'headers'       => array(),
		'retrieve_body' => true,
		'body'          => '',
	);
	$args         = wp_parse_args( $args, $default_args );
	$method       = strtoupper( $method );

	switch ( $method ) {
		case 'GET':
			$res = code_snippets()->api->get_request( $action, $args['headers'] );
			break;
		case 'POST':
			$res = code_snippets()->api->post_request( $action, $args['headers'], $args['body'] );
			break;

		default:
			$res = new WP_Error( 'wpd_api_unknown_method', __( 'Unknown request method' ) );
	}

	if ( is_wp_error( $res ) ) {
		return false;
	}

	if ( $args['retrieve_body'] ) {
		return json_decode( wp_remote_retrieve_body( $res ), true );
	} else {
		return $res;
	}
}

/**
 * @param $args array
 * @param $total
 *
 * @return int|array
 */
function wpd_list_posts( array $args, &$total ) {
	$path = 'posts?' . http_build_query( $args );
	$res  = wpd_request( $path, 'GET', array(
		'retrieve_body' => false,
	) );

	$total = wp_remote_retrieve_header( $res, 'x-wp-total' );

	return is_wp_error( $res ) ? false : json_decode( wp_remote_retrieve_body( $res ), true );
}

/**
 * @param $endpoint string
 *
 * @return bool
 */
function wpd_install_remote_snippet( string $endpoint ): bool {
	$snippet_arr = wpd_request( urldecode( $endpoint ) );
	$snippet     = new WPD_Snippet( $snippet_arr );

	if ( is_wp_error( $snippet ) ) {
		return false;
	}

	$args = array(
		'name'      => $snippet->name,
		'desc'      => $snippet->description,
		'tags'      => array_column( $snippet->request_tags(), 'name' ),
		'code'      => $snippet->code,
		'priority'  => 10,
		'scope'     => 'global',
		'remote'    => true,
		'remote_id' => $snippet->id,
	);

	save_snippet( new Code_Snippet( $args ) );

	return true;
}

/*add_action( 'rest_api_init', 'register_wpd_endpoints' );
function register_endpoints() {
    register_rest_route( 'wpd', '/evaluate', array(
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => 'evaluate_wpd_console' ),
    );
}


function evaluate_wpd_console(WP_REST_Request $request){
	try {
		$timer = microtime( true );
		$input = $request['input'];

		$config = new \Psy\Configuration( [
			'configDir' => WP_CONTENT_DIR,
		] );

		$output = new ShellOutput( ShellOutput::VERBOSITY_NORMAL, true );

		$config->setOutput( $output );
		$config->setColorMode( \Psy\Configuration::COLOR_MODE_DISABLED );

		$psysh = new Shell( $config );

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

		return rest_ensure_response( $data );

	} catch ( Throwable $e ) {
		ob_end_flush();

		return new WP_Error( 'wp_console_rest_error', $e->getMessage(), [
			'input'  => $request['input'],
			'status' => 422,
			'trace'  => $e->getTraceAsString(),
		] );
	}
}*/

