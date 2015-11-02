<?php

namespace Understory;

abstract class CustomTaxonomy extends \TimberTerm implements HasMetaData
{
    use Core;

    public static $taxonomy_name;

    public $core;

    function __construct($tid = null, $tax = '')
    {

        // Create an instance of Core since we are not extending it
        // $this->core = new Core($this);

        parent::__construct($tid, $tax);
    }

    /**
     * Boilerplate code for registering a Custom Taxonomy
     *
     * @param  string $name     Name of the taxonomy (singular)
     * @param  array  $labels   Labels for taxonomy
     * @param  array  $args     Arguments for taxonomy
     * @param  string $plural   Plural name of taxonomy if
     *                          isn't a simple case with an appended 's'
     */
    public static function registerTaxonomy($name, $labels = array(), $args = array(), $plural = "")
    {
        $item = ucwords($name);

        // Slugify the name to use as post type name
        $called_class = get_called_class();
        preg_match('@\\\\([\w]+)$@', $called_class, $matches);
        $taxonomy_name = trim(strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', "-$1", $matches[1])));
        
        // Default plural
        if (empty($plural)) {
            $plural = $item."s";
        }

        $labels = array_merge(array(
            'name' => _x(sprintf('%s', $plural), 'Taxonomy General Name'),
            'singular_name' => _x(sprintf('%s', $item), 'Taxonomy Singular Name'),
            'menu_name' => __(sprintf('%s Taxonomy', $item)),
            'all_items' => __(sprintf('All %s', $plural)),
            'parent_item' => __(sprintf('Parent %s', $item)),
            'parent_item_colon' => __(sprintf('Parent %s:', $item)),
            'new_item_name' => __(sprintf('New %s', $item)),
            'add_new_item' => __(sprintf('Add %s', $item)),
            'edit_item' => __(sprintf('Edit %s', $item)),
            'update_item' => __(sprintf('Update %s', $item)),
            'view_item' => __(sprintf('View %s', $item)),
            'separate_items_with_commas' => __(sprintf('Separate %s with commas', strtolower($plural))),
            'add_or_remove_items' => __(sprintf('Add or remove %s', strtolower($plural))),
            'choose_from_most_used' => __('Choose from the most used'),
            'popular_items' => __(sprintf('Popular %s', $plural)),
            'search_items' => __(sprintf('Search %s', $plural)),
            'not_found' => __('Not Found'),
        ), $labels);

        $args = array_merge(array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => false,
        ), $args);

        \register_taxonomy($taxonomy_name, '', $args);
        static::$taxonomy_name = $taxonomy_name;
    }

    /**
     * Because TimberTerm defines the function name(), our magic
     * __get() method never gets called when trying to use .title in a
     * twig template. This is our work around:
     *
     * If the called class has a function getTitle defined, call that.
     * Otherwise call TimberTerm::title()
     *
     * @return mixed    Category
     */
    public function title()
    {
        $called_class = get_called_class();

        if (method_exists($called_class, 'getTitle')) {
            return $called_class::getTitle();
        } else {
            return parent::title();
        }
    }

    /**
     * Implentation of HasMetaData->getMetaValue
     *
     * @param  string $key Key for the meta field
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
        \update_post_meta($this->ID, $key, true);
    }
}
