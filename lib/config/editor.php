<?php

namespace Understory\Config;

class Editor {

    // Each array child is a format with it's own settings
    static protected $styles = array();

    public function __construct()
    {
        self::init();
    }

    public static function init()
    {
        add_filter('mce_buttons_2', array(__NAMESPACE__.'\\Editor', 'addStyleSelectField'));
        add_filter('tiny_mce_before_init', array(__NAMESPACE__.'\\Editor', 'addStyles'));
        add_action('init', array(__NAMESPACE__.'\\Editor', 'addStyleSheet'));
    }

    public static function addStyleSelectField($buttons)
    {
        array_unshift($buttons, 'styleselect');

        return $buttons;
    }

    public static function addStyles($init_array)
    {
        // Insert the array, JSON ENCODED, into 'style_formats'
        if (count(self::$styles) > 0) {
            $init_array['style_formats'] = json_encode(self::$styles);
        }

        return $init_array;
    }

    public static function addStyleSheet()
    {
        add_editor_style('assets/editor.css');
    }
}