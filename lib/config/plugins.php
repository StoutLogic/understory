<?php

namespace Understory\Config;

class Plugins
{
  /*
   * Array of plugin arrays. Required keys are name and slug.
   * If the source is NOT from the .org repo, then source is also required.
   */
    static protected $plugins = array();

    public function __construct()
    {
        add_action('tgmpa_register', array(get_called_class(), 'requirePlugins'));
    }


    public static function requirePlugins($themeConfig = array())
    {
        if ($themeConfig == "") {
            $themeConfig = array();
        }

        /*
         * Array of configuration settings. Amend each line as needed.
         */
        $defaultConfig = array(
            'id' => 'tgmpa',                      // Unique ID for hashing notices for multiple instances of TGMPA.
            'default_path' => '',                 // Default absolute path to bundled plugins.
            'menu' => 'tgmpa-install-plugins',    // Menu slug.
            'parent_slug' => 'themes.php',        // Parent menu slug.
            'capability' => 'edit_theme_options', // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
            'has_notices' => true,                // Show admin notices or not.
            'dismissable' => false,               // If false, a user cannot dismiss the nag message.
            'dismiss_msg' => '',                  // If 'dismissable' is false, this message will be output at top of nag.
            'is_automatic' => true,               // Automatically activate plugins after installation or not.
            'message' => '',                      // Message to output right before the plugins table.
        );

        $config = array_merge($defaultConfig, $themeConfig);
        \tgmpa(static::$plugins, $config);
    }
}
