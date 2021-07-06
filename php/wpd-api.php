<?php

if (!class_exists('WPD_Snippet'))
    require_once dirname(__FILE__) . "/wpd_snippet.php";

/**
 * @param $action string
 * @param $isUrl bool
 * @return false|array
 */
function wpd_request($action, $isUrl = false, $retrieveBody = true)
{
    $res = code_snippets()->api->request($action, array(), $isUrl, $retrieveBody);

    return is_wp_error($res) ? false : $res;
}

/**
 * @param $page int
 * @param $per_page int
 * @param $total
 * @return int|array
 */
function wpd_list_posts(int $page, int $per_page, &$total)
{
    $path = sprintf("posts?page=%d&per_page=%d", $page, $per_page);
    $res = code_snippets()->api->request($path, array(), false, false);

    $total = wp_remote_retrieve_header($res, "x-wp-total");

    return is_wp_error($res) ? false : json_decode(wp_remote_retrieve_body($res), true);
}

/**
 * @param $endpoint string
 * @return bool
 */
function wpd_install_remote_snippet(string $endpoint)
{
    $snippet_arr = wpd_request(urldecode($endpoint), true);
    $snippet = new WPD_Snippet($snippet_arr);

    if (is_wp_error($snippet))
        return false;

    $args = array(
        "name" => $snippet->name,
        "desc" => $snippet->description,
        "tags" => array_column($snippet->request_tags(), "name"),
        "code" => $snippet->code,
        "priority" => 10,
        "scope" => "global",
        "remote" => true,
        "remote_id" =>  $snippet->id
    );

    save_snippet(new Code_Snippet($args));

    return true;
}