<?php

namespace Understory;

// Create option page to admin the Pillars

class OptionPage implements HasMetaData
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

    public function getTitle()
    {
        return $this->title;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOption($optionName)
    {
        return \get_option($this->getId().'_'.$optionName);
    }

    public function setOption($optionName, $value)
    {
        return \update_option($this->getId().'_'.$optionName, $value);
    }

    /**
     * Implentation of HasMetaData->getMetaValue
     *
     * @param  string $metaFieldKey Key for the meta field
     * @return string                Value of the meta field
     */
    public function getMetaValue($key)
    {
        return $this->getOption($key);
    }

    /**
     * Implentation of HasMetaData->setMetaValue
     *
     * @param  string $key Key for the meta field
     * @param  string $value Value for the meta field
     */
    public function setMetaValue($key, $value)
    {
        $this->setOption($key, $value);
    }
}
