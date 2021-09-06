<?php

#use Psy;
class Code_Snippets_API {
	const WPD_URL = 'https://wpdistro.com/wp-json/wp/v2/';


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

