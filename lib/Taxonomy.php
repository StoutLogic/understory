<?php

namespace Understory;

use Timber;

class Taxonomy extends Timber\Term implements MetaDataBinding, Registerable
{
    private $config = [];

    function __construct($tid = false, $tax = '')
    {
        if ($tid !== false) {
            $this->initConfig();
            parent::__construct($tid, $this->getTaxonomy());
        }
    }

    public function setId($tid)
    {
        $this->init($tid);
    }

    public function autobind()
    {
        $id = $this->get_term_from_query();
        $this->init($id);
    }

    protected function init($tid)
    {
        parent::init($tid);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTaxonomy()
    {
        return $this->taxonomy;
    }

    public function getBindingName()
    {
        return $this->getTaxonomy();
    }

    public function setTaxonomy($taxonomy)
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

    public function setLabel($key, $value)
    {
        return $this->setLabelConfig($key, $value);
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
        $item = ucwords(str_replace('-', ' ', $this->getTaxonomy()));
        $plural = $item . 's';

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
            'separate_items_with_commas' => __(sprintf('Separate %s with commas',
                strtolower($plural))),
            'add_or_remove_items' => __(sprintf('Add or remove %s',
                strtolower($plural))),
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

    private function initConfig()
    {
        $this->config = $this->getDefaultConfiguration();
    }

    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
        return $this;
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

    public function setRewrite($key, $value)
    {
        return $this->setRewriteConfig($key, $value);
    }

    public function setSlug($slug)
    {
        return $this->setRewriteConfig('slug', $slug);
    }

    public function register()
    {
        register_taxonomy($this->getTaxonomy(), '', $this->getConfig());
    }

    /**
     * Override Timber\Term implementation, without the surrounding <p>
     * @return string
     */
    public function getDescription()
    {
        return term_description($this->ID);
    }

    /**
     * Implentation of MetaDataBinding::getMetaValue
     *
     * @param  string $key Key for the meta field
     * @return string                Value of the meta field
     */
    public function getMetaValue($key)
    {
        return \get_term_meta($this->ID, $key, true);
    }

    /**
     * Implentation of MetaDataBinding::setMetaValue
     *
     * @param  string $key Key for the meta field
     * @param  string $value Value for the meta field
     */
    public function setMetaValue($key, $value)
    {
        \update_term_meta($this->ID, $key, true);
    }

    public function getPosts(CustomPostType $post, $args = [])
    {
        $args = array_merge([
            'numberposts' => -1,
            'post_type' => $post->getBindingName(),
        ], $args);

        return $this->get_posts($args, get_class($post));
    }

    /**
     * Order of method calls:
     *
     * 1. getMethodName($args)
     * 2. methodName($args)
     * 3. propertyName
     * 4. fall back to Timber's core implementation
     *
     * @param string $propertyName
     * @param array $args
     * @return mixed
     */
    public function __call($propertyName, $args = [])
    {
        if (method_exists($this, 'get'.$propertyName)) {
            return call_user_func_array([$this, 'get'.$propertyName], $args);
        } else if (method_exists($this, $propertyName)) {
            return call_user_func_array([$this, $propertyName], $args);
        } else if (property_exists($this, $propertyName)) {
            return $this->$propertyName;
        }

        return parent::__call($propertyName, $args);
    }
}
