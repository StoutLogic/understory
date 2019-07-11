<?php
/**
 * Understory Initialization
 */

/**
 * Allow theme to keep wordpress templates in app/Views
 */
\add_filter('template_directory', function ($template_dir, $template, $theme_root) {
    if (!defined('TEMPLATEPATH')) {
        // Bail if parent theme isn't an Understory Theme
        if (!file_exists($template_dir . '/app/Site.php')) {
            return $template_dir;
        }

        if (file_exists($template_dir . '/app/Views')) {
            $template_dir .= '/app/Views';
        }

    }
    return $template_dir;
}, 10000, 3);

\add_filter('stylesheet_directory', function ($template_dir, $template, $theme_root) {
    if (!defined('TEMPLATEPATH')) {
        if (file_exists($template_dir . '/app/Views')) {
            $template_dir .= '/app/Views';

        }
    }
    return $template_dir;
}, 10000, 3);

// If our custom template name contains double /app/Views/app/Views/ path in it, remove it.
\add_filter('page_template_hierarchy', function($templates) {
    return array_map(function ($template) {
        return str_replace('app/Views/app/Views/', 'app/Views/', $template);
    }, $templates);
}, 10000);


\add_filter('theme_page_templates', function ($page_templates, $theme, $post, $posttype) {

    $files = (array) $theme->get_files('php', 2, true);

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

    return $page_templates;

}, 10000, 4);
