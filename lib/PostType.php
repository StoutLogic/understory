<?php

namespace Understory;

class PostType extends \Timber\Post implements MetaDataBinding, Registerable
{
    // use Core;
    private $postType;

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
            $this->setPost($post);
        }
    }

    /**
     * Set and Init Post
     * If nothing is passed, WordPress's current post will try to be determined
     * @param mixed $post Post, Post ID or null
     */
    public function setPost($post = null)
    {
        if (!is_object($post)) {
            $post = $this->determine_id($post);
        }
        $this->init($post);
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
            $this->config = $this->getDefaultConfiguration();
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

    public function register()
    {
        register_post_type($this->getPostType(), $this->getConfig());
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
            'supports' => ['title', 'editor', 'thumbnail'],
            'rewrite' => true,
            'show_in_nav_menus' => true,
        ];

        return $args;
    }

    /**
     * Register this post type with an already created Taxonomy
     * Hook this function into the 'init' action.
     *
     * @param  string $taxonomy_name Taxonomy name
     * @return -
     */
    public static function registerTaxonomy($taxonomyClass)
    {
        $called_class = get_called_class();

        static::$taxonomies[$taxonomyClass::$taxonomy_name] = $taxonomyClass;
        \register_taxonomy_for_object_type($taxonomyClass::$taxonomy_name, $called_class::$cpt_name);
    }

    /**
     * Allows this post type to be sortable by Advance Custom Post Type Order
     * @param  array $post_type_array allowed post types
     * @return array                  allowed post types
     */
    public static function apoSortable($post_type_array)
    {
        $post_type_array[] = self::$cpt_name;
        return $post_type_array;
    }

    public static function findRecent($limit = -1, $offset = 0, $args = array())
    {
        return self::findAll($limit, $offset, $args);
    }

    public static function findAll($limit = -1, $offset = 0, $args = array())
    {
        $called_class = get_called_class();

        $args = array_merge(array(
            'post_type' => array_keys($called_class::$post_types),
            'posts_per_page' => $limit,
            'offset' => $offset,
        ), $args);

        $results =  \Timber::get_posts($args, $called_class::$post_types, true);
        return $results;
    }

    /**
     * Find posts by id
     *
     * @param  mixed $id a single or array of ids
     * @return array     Array of Posts
     */
    public static function find($id)
    {
        if (!is_array($id)) {
            $id = array($id);
        }

        return self::findRecent(-1, 0, array(
            'post__in' => $id
        ));
    }

    /**
     * Find post by slug
     *
     * @param  string $slug  slug of post
     * @return post
     */
    public static function findBySlug($slug)
    {
        $called_class = get_called_class();

        $args = array(
            'post_type' => array_keys($called_class::$post_types),
            'name' => $slug,
        );

        return \Timber::get_post($args, $called_class::$post_types);
    }

    /**
     * Because TimberPost defines the function category(), our magic
     * __get() method never gets called when trying to use .category in a
     * twig template. This is our work around:
     *
     * If the called class has a function getCategory defined, call that.
     * Otherwise call TimberPost::category()
     *
     * @return mixed    Category
     */
    public function category()
    {
        $called_class = get_called_class();

        if (method_exists($called_class, 'getCategory')) {
            return $called_class::getCategory();
        } else {
            return parent::category();
        }
    }

    /**
     * Implentation of MetaDataBinding::getMetaValue
     *
     * @param  string $metaFieldKey Key for the meta field
     * @return string                Value of the meta field
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
