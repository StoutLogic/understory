<?php

namespace Understory;

abstract class CustomPostType extends \TimberPost implements HasMetaData
{
    use Core;

    // These static variables must be implemented in each child cliass
    public static $cpt_name;
    protected static $taxonomies = array();
    protected static $post_types = array();

    /**
     * If we are supplied a post then we try to instantiate as the correct
     * custom post type, otherwise call the normal constructor
     *
     * @param WP_Post $post Post we are trying to instantiate as a TimberPost
     */
    public function __construct($post = null)
    {
        if (is_object($post)) {
            $this->init($post->ID);
        } else {
            parent::__construct($post);
        }

    }

    /**
     * Boilerplate code for registering a Custom Post Type
     *
     * @param  string $name     Name of the custom post type (singular)
     * @param  array  $labels   Labels for custom post type
     * @param  array  $args     Arguments for custom post type
     * @param  string $plural   Plural name of custom post type if
     *                          isn't a simple case with an appended 's'
     */
    public static function registerPostType($name, $labels = array(), $args = array(), $plural = "")
    {
        $item = ucwords($name);


        // Default plural
        if (empty($plural)) {
            $plural = $item."s";
        }

        $labels = array_merge(array(
            'name' => _x(sprintf('%s', $plural), 'post type general name'),
            'singular_name' => _x(sprintf('%s', $item), 'post type singular name'),
            'add_new' => _x('Add New', sprintf('%s', $item)),
            'add_new_item' => __(sprintf('Add New %s', $item)),
            'edit_item' => __(sprintf('Edit %s', $item)),
            'new_item' => __(sprintf('New %s', $item)),
            'view_item' => __(sprintf('View %s', $item)),
            'search_items' => __(sprintf('Search %s', $plural)),
            'not_found' => __('Nothing found'),
            'not_found_in_trash' => __('Nothing found in Trash'),
            'parent_item_colon' => ''
            ), $labels);
     
        $args = array_merge(array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'query_var' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'has_archive' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => true,
            'show_in_nav_menus' => true,
            ), $args);

        // Create Custom Post Type name, strip out namespaces and slugify
        $called_class = get_called_class();
        preg_match('@\\\\([\w]+)$@', $called_class, $matches);
        $cpt_name = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', "-$1", $matches[1]));

        $error = \register_post_type($cpt_name, $args);

        if (\is_wp_error($error)) {
            \add_action('admin_notices', function () use ($cpt_name, $error) {
                foreach ($error->errors as $error_type => $messages) {
                    $message .= implode("<br>", $messages);
                }
                echo "<div class='error'> <p>Error registering post type: <strong>".$cpt_name."</strong> - $message</p></div>";
            });
        }

        static::$cpt_name = $cpt_name;
        $called_class::registerPostTypeClass($cpt_name);
    }

    /**
     * Save custom post type name and class name
     *
     * @param  string $cpt_name wordpress custom post type
     */
    public static function registerPostTypeClass($cpt_name)
    {
        $called_class = get_called_class();
        $called_class::$post_types[$cpt_name] = $called_class;

        self::$post_types[$cpt_name] = $called_class;
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
     * Implentation of HasMetaData->getMetaValue
     *
     * @param  string $metaFieldKey Key for the meta field
     * @return string                Value of the meta field
     */
    public function getMetaValue($key)
    {
        return \get_post_meta($this->ID, $key, true);
    }
    
    /**
     * Implentation of HasMetaData->setMetaValue
     *
     * @param  string $key Key for the meta field
     * @param  string $value Value for the meta field
     */
    public function setMetaValue($key, $value)
    {
        \update_post_meta($this->ID, $key, $value);
    }
}