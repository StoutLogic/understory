<?php

namespace Understory\Config;

class Editor
{
    // Each array child is a format with it's own settings
    static protected $styles = array();

    public function __construct()
    {
        static::init();
    }

    protected function init()
    {
        add_filter('mce_buttons_2', array($this, 'addStyleSelectField'));
        add_filter('tiny_mce_before_init', array($this, 'addStyles'));
        add_action('init', array($this, 'addStyleSheet'));
    }

    public function addStyleSelectField($buttons)
    {
        array_unshift($buttons, 'styleselect');

        return $buttons;
    }

    public function addStyles($init_array)
    {
        if (self::$styles) {
            // Insert the array, JSON ENCODED, into 'style_formats'
            if (count(self::$styles) > 0) {
                $init_array['style_formats'] = json_encode(self::$styles);
            }

        }
        return $init_array;
    }

    public function addStyleSheet()
    {
        
        exit();
    }
}
