<?php

namespace Understory;

class Taxonomy extends \Timber\Term implements MetaDataBinding, Registerable
{
    private $config = [];

    function __construct($tid = null, $tax = '')
    {
        if ($tid) {
            parent::__construct($tid, $tax);
        }
    }

    public function setId($tid)
    {
        $this->init($tid);
    }

    public function getName()
    {
        return $this->taxonomy;
    }

    public function setName($taxonomy)
    {
        $this->taxonomy = $taxonomy;
        return $this;
    }

    public function setLabelConfig($key, $value)
    {
        $labels = $this->getConfig('labels') ?: [];
        $labels[$key] = $value;
        return $this->setConfig('labels', $labels);
    }

    public function setLabelName($name)
    {
        return $this->setLabelConfig('singular_name', $name);
    }

    public function setLabelPlural($plural)
    {
        return $this->setLabelConfig('name', $plural);
    }

    private function getDefaultConfiguration()
    {
        $item = ucwords(str_replace('-', ' ', $this->getName()));
        $plural = $item.'s';

        $labels = [
            'name' => sprintf('%s', $plural),
            'singular_name' => sprintf('%s', $item),
            'menu_name' => __(sprintf('%s', $item)),
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
        ];

        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => false,
        ];

        return $args;
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

    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
        return $this;
    }

    public function register()
    {
        register_taxonomy($this->getName(), '', $this->getConfig());
    }

    /**
     * Implentation of MetaDataBinding::getMetaValue
     *
     * @param  string $key Key for the meta field
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
        \update_post_meta($this->ID, $key, true);
    }
}
