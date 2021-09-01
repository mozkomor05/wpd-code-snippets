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

/**
 * Pushes snippet to remote DB
 *
 * @param $id int Snippet ID
 *
 * @return bool
 */
function wpd_push_snippet( $id ) {

	$site_url = get_home_url();

	$snippet = get_snippet( $id );

	$snippet_url = preg_replace( '/[[:space:]]+/', '-', strtolower( $snippet->name ) );

	if ( isset( $_POST['desc'] ) ) {
		$snippet_desc = $_POST['desc'];
	} else {
		$snippet_desc = "";
	}

	$username            = 'username';
	$password            = 'pass';
	$rest_api_url_create = 'https://wpdistro.com/wp-json/wp/v2/posts';

	$data_string = wp_json_encode( [
		'title'          => $snippet->name,
		'content'        => $snippet_desc . '
                <br>
                | This snippet was pushed from <strong>' . $site_url . '</strong>
            ',
		'status'         => 'publish',
		'featured_media' => '499',
	] );

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $rest_api_url_create );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );

	curl_setopt( $ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Content-Length: ' . strlen( $data_string ),
		'Authorization: Basic ' . base64_encode( $username . ':' . $password ),
	] );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

	$result   = curl_exec( $ch );
	$response = json_decode( $result, true );

	if ( curl_errno( $ch ) ) {
		$error_msg = curl_error( $ch );
		var_dump( $error_msg );
	}

	curl_close( $ch );

	$rest_api_url_edit = 'https://wpdistro.com/wp-json/acf/v3/posts/' . $response["id"] . '/code';
	$code              = json_encode( [
		'fields' => [
			'code' => $snippet->code
		]
	] );

	$ch2 = curl_init();
	curl_setopt( $ch2, CURLOPT_URL, $rest_api_url_edit );
	curl_setopt( $ch2, CURLOPT_PUT, 0 );
	curl_setopt( $ch2, CURLOPT_POSTFIELDS, $code );

	curl_setopt( $ch2, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Content-Length: ' . strlen( $code ),
		'Authorization: Basic ' . base64_encode( $username . ':' . $password ),
	] );

	curl_setopt( $ch2, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $ch2, CURLOPT_RETURNTRANSFER, true );
	if ( curl_errno( $ch2 ) ) {
		$error_msg = curl_error( $ch2 );
		var_dump( $error_msg );
	}
	$result = curl_exec( $ch2 );
	curl_close( $ch2 );


	// Set remote_id in the database

	global $wpdb;
	$table = "wp_snippets";

	$remote_id = $response["id"];

	$wpdb->update( $table, array( 'remote' => '1' ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
	$wpdb->update( $table, array( 'remote_id' => $remote_id ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );

}
