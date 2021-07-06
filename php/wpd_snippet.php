<?php

class WPD_Snippet
{
    public $raw_array;

    public $id;
    public $name;
    public $url;
    public $description;
    public $slug;
    public $code;

    public $author_endpoint;
    public $self_endpoint;

    const plugins_allowed_tags = array(
        'a' => array(
            'href' => array(),
            'title' => array(),
            'target' => array(),
        ),
        'abbr' => array('title' => array()),
        'acronym' => array('title' => array()),
        'code' => array(),
        'pre' => array(),
        'em' => array(),
        'strong' => array(),
        'ul' => array(),
        'ol' => array(),
        'li' => array(),
        'p' => array(),
        'br' => array(),
    );

    public function __construct($fields)
    {
        $this->raw_array = $fields;

        $this->id = $fields['id'];
        $this->name = wp_kses($fields['title']['rendered'], self::plugins_allowed_tags);
        $this->slug = $fields["slug"];
        $this->url = $fields["link"];
        $this->self_endpoint = $fields["_links"]["self"][0]["href"];
        $this->description = $fields['content']['rendered'];
        $this->author_endpoint = $fields['_links']['author'][0]['href'];
        $this->code = $fields["acf"]["code"];
    }

    public function request_author($force_request = false)
    {
        $data = null;

        if (!$force_request && is_array($this->raw_array) && array_key_exists('author', $this->raw_array)) {
            $author = $this->raw_array['author'];

            if (array_key_exists('name', $author))
                $data = $author;
        }

        if (empty($data))
            $data = wpd_request($this->author_endpoint, true);

        return (object)array(
            "name" => wp_kses($data['name'], self::plugins_allowed_tags),
            "link" => $data['link']
        );
    }

    public function request_tags($force_request = false)
    {
        if (!$force_request && is_array($this->raw_array) && array_key_exists('tags', $this->raw_array)) {
            $tags = $this->raw_array['tags'];

            if (count($tags) == 0 || array_key_exists('name', $tags[0]))
                return array_map(function ($x) {
                    return (object)$x;
                }, $tags);
        }

        $tags = array();
        foreach ($this->raw_array["_links"]["wp:term"] as $i => $term) {
            if ($term["taxonomy"] !== "post_tag")
                continue;

            $tag = wpd_request($term["href"], true);

            if (!$tag)
                continue;

            $tag = $tag[0];

            $tags[] = (object)array(
                "name" => $tag["name"],
                "link" => $tag["link"]
            );
        }

        return $tags;
    }
}