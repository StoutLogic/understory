<?php

namespace Understory;

use Timber;

class PostType extends Timber\Post implements MetaDataBinding, Registerable
{
    // use Core;
    private $postType;

    private $revisionLimit = 10;

    private $config = [];

    /**
     * If we are supplied a post then we try to instantiate as the correct
     * custom post type, otherwise call the normal constructor
     *
     * @param WP_Post $post Post we are trying to instantiate as a TimberPost
     */
    public function __construct($post = null)
    {
        if ($post) {
            $this->initConfig();
            parent::__construct($post);
        }
    }

    /**
     * Set and Init Post
     * If nothing is passed, WordPress's current post will try to be determined
     * @param mixed $post Post, Post ID or null
     */
    public function setPost($post = null)
    {
        $this->initConfig();
        parent::__construct($post);
    }

    private function initConfig()
    {
        $this->config = $this->getDefaultConfiguration();
    }

    public function load()
    {
        $this->setPost(null);
    }

    /**
     * Set the post type name, used in the database
     * @param string $postType
     * @return $this
     */
    public function setPostType($postType)
    {
        $this->postType = $postType;
        return $this;
    }

    /**
     * Get teh post type name, used in the database
     * @return string
     */
    public function getPostType()
    {
        return $this->postType;
    }

    public function getBindingName()
    {
        return $this->getPostType();
    }

    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
        return $this;
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

    public function setRewriteConfig($key, $value)
    {
        $rewrite = $this->getConfig('rewrite');
        if (!is_array($rewrite)) {
            $rewrite = [];
        }
        $rewrite[$key] = $value;
        return $this->setConfig('rewrite', $rewrite);
    }

    public function setSupports($supports)
    {
        return $this->setConfig('supports', $supports);
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

    public function setMenuIcon($icon)
    {
        return $this->setConfig('menu_icon', $icon);
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

    public function setLabelConfig($key, $value)
    {
        $labels = $this->getConfig('labels') ?: [];
        $labels[$key] = $value;
        return $this->setConfig('labels', $labels);
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

    public function register()
    {
        if ($this->getPostType() !== 'page') {
            register_post_type($this->getPostType(), $this->getConfig());
        }
        add_filter( 'wp_revisions_to_keep', function($num, $post) {
            if ($post->post_type === $this->getBindingName()) {
                return $this->getRevisionLimit();
            }
            return $num;
        }, 10, 2 );
    }

    private function getDefaultConfiguration()
    {
        $item = ucwords($this->getPostType());
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

    /**
     * Implentation of MetaDataBinding::getMetaValue
     *
     * @param  string $key Key for the meta field
     * @return string Value of the meta field
     */
    public function getMetaValue($key)
    {
        return \get_post_meta($this->ID, $key, true);
    }

    /**
     * Implentation of MetaDataBinding::setMetaValue
     *
     * @param  string $key Key for the meta field
     * @param  string $value Value for the meta field
     */
    public function setMetaValue($key, $value)
    {
        \update_post_meta($this->ID, $key, $value);
    }
}
