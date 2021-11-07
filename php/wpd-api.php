<?php

if ( ! class_exists( 'WPD_Snippet' ) ) {
	require_once dirname( __FILE__ ) . '/wpd_snippet.php';
}

/**
 * @param string $action Endpoint (or whole URL) to request.
 * @param string $method Request method.
 * @param array  $args   Additional args.
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
	$path = 'code_snippet?' . http_build_query( $args );
	$res  = wpd_request( $path, 'GET', array(
		'retrieve_body' => false,
	) );

	$total = wp_remote_retrieve_header( $res, 'x-wp-total' );

	return is_wp_error( $res ) ? false : json_decode( wp_remote_retrieve_body( $res ), true );
}

/**
 * @param string $endpoint Endpoint.
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
		'name'          => $snippet->name,
		'desc'          => $snippet->description,
		'tags'          => array_column( $snippet->request_tags(), 'name' ),
		'code'          => $snippet->code,
		'priority'      => 10,
		'scope'         => 'global',
		'remote_status' => 'remote',
		'remote_id'     => $snippet->id,
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
	$snippet = get_snippet( $id );

	if ( $snippet->remote_status !== 'local' ) {
		return false;
	}

	$args = array(
		'body' => wp_json_encode( array(
			'title'   => $snippet->name,
			'content' => $snippet->desc,
			'code'    => $snippet->code,
		) ),
	);

	wpd_request( code_snippets()->api::PUSH_URL, 'POST', $args );

	return true;
}


/**
 * @param int $remote_id ID of remote snippet.
 *
 * @return bool exists?
 */
function wpd_remote_snippet_exists( $remote_id ): bool {
	/** @var wpdb $wpdb */
	global $wpdb;

	$db     = code_snippets()->db;
	$table  = $db->get_table_name();
	$result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM {$table} WHERE remote_id = %d", $remote_id ) );

	return 1 === $result;
}
