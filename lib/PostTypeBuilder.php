<?php

namespace Understory;

use Doctrine\Common\Inflector\Inflector;

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

    public function setLabel($key, $value)
    {
        return $this->setLabelConfig($key, $value);
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

    public function setSlug($slug)
    {
        return $this->setRewriteConfig('slug', $slug);
    }

    public function setSupports($supports)
    {
        return $this->setConfig('supports', $supports);
    }

    public function addSupport($support)
    {
        $supports = $this->getConfig('supports') ?: [];
        $supports[] = $support;

        return $this->setSupports($supports);
    }

    public function removeSupport($support)
    {
        $supports = $this->getConfig('supports') ?: [];
        $index = array_search($support, $supports);
        if ($index !== false) {
            array_splice($supports, $index, 1);
        }

        return $this->setSupports($supports);
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
        global $wp_post_types;

        // Is the post type already registered, i.e. a default post type?
        if (isset($wp_post_types[$this->getPostTypeName()])) {
            $config = [];
            // Convert any stdobjects to an array to manipulate
            foreach ($wp_post_types[$this->getPostTypeName()] as $key => $value) {
                $config[$key] = $value;
                if (is_object($value)) {
                    $config[$key] = (array) $value;
                }
            }
            return $config;
        }

        return $this->generateDefaultConfiguration();
    }

    private function generateLabels($singular, $plural = null)
    {
        if (!$plural) {
            $plural = Inflector::pluralize($singular);
        }

        return [
            'name' => sprintf('%s', $plural),
            'singular_name' => sprintf('%s', $singular),
            'add_new' => 'Add New', sprintf('%s', $singular),
            'add_new_item' => sprintf('Add New %s', $singular),
            'edit_item' => sprintf('Edit %s', $singular),
            'new_item' => sprintf('New %s', $singular),
            'view_item' => sprintf('View %s', $singular),
            'search_items' => sprintf('Search %s', $plural),
            'not_found' =>  sprintf('No %s found', strtolower($plural)),
            'not_found_in_trash' =>  sprintf('No %s found in trash', strtolower($plural)),
            'parent_item_colon' => sprintf('Parent %s:', $singular),
            'all_items' => sprintf('All %s', $plural),
            'archives' => sprintf('%s Archives', $singular),
            'insert_into_item' => sprintf('Insert into %s:', strtolower($singular)),
            'uploaded_to_this_item' => sprintf('Upload to this %s', strtolower($singular)),
            'filter_items_list' => sprintf('Filter %s list', strtolower($plural)),
            'items_list_navigation' => sprintf('%s list navigation ', $plural),
            'items_list' => sprintf('%s List', $plural),
            'menu_name' => $plural,
            'name_admin_bar' => $singular,
        ];
    }

    private function generateDefaultConfiguration()
    {
        $item = ucwords(str_replace('-', ' ', $this->getPostTypeName()));

        $args = [
            'labels' => $this->generateLabels($item),
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