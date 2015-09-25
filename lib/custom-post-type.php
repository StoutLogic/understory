<?php

namespace Understory;

abstract class CustomPostType extends \TimberPost
{
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
        // Create an instance of Core since we are not extending it
        $this->core = new Core($this);

        if ($post !== null) {
            $this->init($post->ID);
        } else {
            parent::__construct($post);
        }

    }

    /**
     *  Overwrite TimberCore's __get method with our own Core __get method
     */
    function __get($field)
    {
        $this->core->__get($field);
    }

    /**
     *  Overwrite TimberCore's import method with our own Core import method
     */
    function import($info, $force = false)
    {
        $this->core->import($info, $force);
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
}
