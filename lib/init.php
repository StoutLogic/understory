<?php
/**
 * Understory Initialization
 */

/**
 * Allow theme to keep wordpress templates in app/views or app/Views
 */
\add_filter('template_directory', function ($template_dir, $template, $theme_root) {
    if (!defined('TEMPLATEPATH')) {
        if (file_exists($template_dir . '/app/Views')) {
            $template_dir .= '/app/Views';
        } else if (file_exists($template_dir . '/app/views')) {
            $template_dir .= '/app/views';
        }
    }
    return $template_dir;
}, 10000, 3);

\add_filter('theme_page_templates', function ($page_templates, $theme, $post) {

    if (count($page_templates) == 0) {

        $files = (array) $theme->get_files('php', 2);
        // print_r($files);

        foreach ($files as $file => $full_path) {
            if (! preg_match('|Template Name:(.*)$|mi', file_get_contents($full_path), $header)) {
                continue;
            }
            $page_templates[ $file ] = _cleanup_header_comment($header[1]);
        }

        // Hack by adding to the cache until WordPress allows us to add templates
        $cache_hash = md5( $theme->get_stylesheet_directory() );
        $key = "page_templates-{$cache_hash}";
        $group = 'themes';
        wp_cache_set( $key, $page_templates, $group, 1800 );
    }

    return $page_templates;

}, 10000, 3);
