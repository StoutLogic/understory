<?php

namespace Understory;

use Doctrine\Common\Inflector\Inflector;

class TaxonomyBuilder implements Builder
{
    private $config = [];

    private $taxonomyName;

    public function __construct($taxonomyName)
    {
        $this->taxonomyName = $taxonomyName;
        $this->initConfig();
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

    public function setLabelPlural($plural)
    {
        return $this->setLabelConfig('name', $plural);
    }

    private function getDefaultConfiguration()
    {
        global $wp_taxonomies;

        // Is the post type already registered, i.e. a default post type?
        if (isset($wp_taxonomies[$this->getTaxonomyName()])) {
            $config = [];
            // Convert any stdobjects to an array to manipulate
            foreach ($wp_taxonomies[$this->getTaxonomyName()] as $key => $value) {
                $config[$key] = $value;
                if (is_object($value)) {
                    $config[$key] = (array) $value;
                }
            }
            return $config;
        }

        return $this->generateDefaultConfiguration();
    }

    public function setLabelName($singular, $plural = null)
    {
        return $this->setConfig(
            'labels',
            array_merge(
                $this->getConfig('labels'),
                $this->generateLabels($singular, $plural)
            )
        );
    }

    private function generateLabels($singular, $plural = null)
    {
        if (!$plural) {
            $plural = Inflector::pluralize($singular);
        }

        return [
            'name' => sprintf('%s', $plural),
            'singular_name' => sprintf('%s', $singular),
            'menu_name' => __(sprintf('%s', $plural)),
            'all_items' => __(sprintf('All %s', $plural)),
            'parent_item' => __(sprintf('Parent %s', $singular)),
            'parent_item_colon' => __(sprintf('Parent %s:', $singular)),
            'new_item_name' => __(sprintf('New %s', $singular)),
            'add_new_item' => __(sprintf('Add %s', $singular)),
            'edit_item' => __(sprintf('Edit %s', $singular)),
            'update_item' => __(sprintf('Update %s', $singular)),
            'view_item' => __(sprintf('View %s', $singular)),
            'separate_items_with_commas' => __(sprintf('Separate %s with commas',
                strtolower($plural))),
            'add_or_remove_items' => __(sprintf('Add or remove %s',
                strtolower($plural))),
            'choose_from_most_used' => __('Choose from the most used'),
            'popular_items' => __(sprintf('Popular %s', $plural)),
            'search_items' => __(sprintf('Search %s', $plural)),
            'not_found' => __('Not Found'),
        ];
    }

    private function generateDefaultConfiguration()
    {
        $item = ucwords(str_replace('-', ' ', $this->getTaxonomyName()));

        $labels = $this->generateLabels($item);

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


    /**
     * @return string
     */
    public function getTaxonomyName()
    {
        return $this->taxonomyName;
    }

    public function setTaxonomyName($taxonomyName)
    {
        $this->taxonomyName = $taxonomyName;
        return $this;
    }

    public function build()
    {
        return $this->config;
    }
}