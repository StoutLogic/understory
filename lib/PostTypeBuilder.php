<?php

namespace Understory;

class PostTypeBuilder implements Builder
{
    private $config = [];

    private $postTypeName;

    private $revisionLimit = 10;

    public function __construct($postTypeName)
    {
        $this->postTypeName = $postTypeName;
        $this->initConfig();
    }

    public function getConfig($key = null)
    {
        if (empty($this->config)) {
            $this->initConfig();
        }
        if ($key) {
            if (array_key_exists($key, $this->config)) {
                return $this->config[$key];
            }
            return null;
        }
        return $this->config;
    }

    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
        return $this;
    }

    public function setLabelConfig($key, $value)
    {
        $labels = $this->getConfig('labels') ?: [];
        $labels[$key] = $value;
        return $this->setConfig('labels', $labels);
    }

    public function setRewriteConfig($key, $value)
    {
        $rewrite = $this->getConfig('rewrite');
        if (!is_array($rewrite)) {
            $rewrite = [];
        }
        $rewrite[$key] = $value;
        return $this->setConfig('rewrite', $rewrite);
    }

    public function setLabelName($name)
    {
        return $this->setLabelConfig('singular_name', $name);
    }

    public function setLabelPlural($plural)
    {
        return $this->setLabelConfig('name', $plural);
    }

    public function setSlug($slug)
    {
        return $this->setRewriteConfig('slug', $slug);
    }

    public function setSupports($supports)
    {
        return $this->setConfig('supports', $supports);
    }

    public function setMenuIcon($icon)
    {
        return $this->setConfig('menu_icon', $icon);
    }

    /**
     * Set the Menu Icon to be a Dashicon
     * @param string $icon without the `dashicons-`
     * @return $this;
     */
    public function setDashicon($icon)
    {
        return $this->setMenuIcon('dashicons-'.$icon);
    }

    public function getRevisionLimit()
    {
        return $this->revisionLimit;
    }

    public function setRevisionLimit($revisionLimit)
    {
        $this->revisionLimit = $revisionLimit;
        return $this;
    }

    /**
     * Set the post type name, used in the database
     * @param string $postTypeName
     * @return $this
     */
    public function setPostTypeName($postTypeName)
    {
        $this->postTypeName = $postTypeName;
        return $this;
    }

    /**
     * Get teh post type name, used in the database
     * @return string
     */
    public function getPostTypeName()
    {
        return $this->postTypeName;
    }

    private function initConfig()
    {
        $this->config = $this->getDefaultConfiguration();
    }

    private function getDefaultConfiguration()
    {
        $item = ucwords($this->getPostTypeName());
        $plural = $item.'s';

        $labels = [
            'name' => sprintf('%s', $plural),
            'singular_name' => sprintf('%s', $item),
            'add_new' => 'Add New', sprintf('%s', $item),
            'add_new_item' => sprintf('Add New %s', $item),
            'edit_item' => sprintf('Edit %s', $item),
            'new_item' => sprintf('New %s', $item),
            'view_item' => sprintf('View %s', $item),
            'search_items' => sprintf('Search %s', $plural),
            'not_found' => 'Nothing found',
            'not_found_in_trash' => 'Nothing found in Trash',
            'parent_item_colon' => ''
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'query_var' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'has_archive' => false,
            'menu_position' => null,
            'supports' => ['title', 'editor', 'thumbnail', 'revisions'],
            'rewrite' => true,
            'show_in_nav_menus' => true,
        ];

        return $args;
    }

    public function build()
    {
        return $this->config;
    }
}