<?php

class Code_Snippets_API
{
    const WPD_URL = "https://wpdistro.com/wp-json/wp/v2/";

    public function request($action, $headers, $isUrl, $retrieveBody)
    {
        global $wp_version;

        $http_args = array_merge(array(
            'timeout' => 15,
            'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url('/'),
        ), $headers);

        $request = wp_remote_get(($isUrl ? "" : self::WPD_URL) . $action, $http_args);

        if (is_wp_error($request)) {
            $res = new WP_Error(
                'wpd_api_failed',
                sprintf(
                    __('An unexpected error occurred. Failed to fetch WPDistro API.', 'code-snippets')
                ),
                $request->get_error_message()
            );
        } else {
            if ($retrieveBody)
                $res = json_decode(wp_remote_retrieve_body($request), true);
            else
                $res = $request;
        }

        return $res;
    }
}
