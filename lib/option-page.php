<?php

namespace Understory;

// Create option page to admin the Pillars

class OptionPage
{
    private $title = "";
    private $id = "";

    public function __construct($title, $config = array())
    {
        $this->title = $title;
        $this->id = str_replace(" ", "-", strtolower($title));

        $this->config = array_merge(
            array(
                'page_title' => $this->title,
                'menu_title' => $this->title,
                'menu_slug' => $this->id,
                'capability' => 'edit_posts',
                'position' => 53,
                'icon_url' => 'dashicons-admin-generic',
                'redirect' => false,
                'post_id' => $this->id,
            ),
            $config
        );

        if (function_exists('acf_add_options_page')) {
            $page = acf_add_options_page($this->config);
        }
    }
}
