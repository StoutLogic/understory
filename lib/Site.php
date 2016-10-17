<?php

namespace Understory;

use Doctrine\Common\Inflector\Inflector;
use Understory\Config;
use \Timber\Timber;

class Site extends \Timber\Site
{
    protected static $themeSupport = array();
    protected $optionPages = array();
    protected $editor;

    protected $views = array();

    protected $pageTitle;

    public function __construct()
    {
        Timber::$dirname = 'app/templates';

        self::addThemeSupport();

        static::registerComponents();
        static::addEditorConfig();

        \add_filter('timber_context', array( $this, 'initializeContext' ));
        \add_filter('get_twig', array( $this, 'addToTwig' ));

        \add_action('init', array( $this, 'registerViews' ), 11);
        \add_action('init', array( $this, 'registerTaxonomies' ), 12);
        \add_action('init', array( $this, 'registerPostTypes' ), 13);
        \add_action('init', array( $this, 'registerOptionPages' ), 14);
        \add_action('init', array( $this, 'registerNavigations' ), 15);

        \add_action('admin_menu', array( $this, 'customizeAdminMenu' ), 10);

        \add_action('wp_enqueue_scripts', array( $this, 'enqueScripts'), 100);
        \add_action('wp_enqueue_scripts', array( $this, 'enqueDefaultStylesheets'), 100000);
        \add_action('wp_enqueue_scripts', array( $this, 'enqueStylesheets'), 102);
        \add_action('admin_enqueue_scripts', array( $this, 'enqueAdminStylesheets'), 100);

        \add_filter('wp_title', array( $this, 'wpTitle' ));

        \add_filter('template_include', array( $this, 'renderView' ), 100000);

        // Warm custom template cache
        \add_action('init', array( $this, 'loadPageTemplates' ), 1);

        parent::__construct();
    }

    public function loadPageTemplates()
    {
        \wp_get_theme()->get_page_templates(null);
    }

    protected function registerComponents()
    {
    }

    protected function addEditorConfig()
    {
        $this->editor = new Config\Editor();
    }

    protected function addThemeSupport()
    {
        foreach (static::$themeSupport as $themeSupport) {
            \add_theme_support($themeSupport);
        }
    }

    public function enqueStylesheets()
    {
    }

    public function enqueDefaultStylesheets()
    {
        \wp_enqueue_style('site', \get_template_directory_uri().'/assets/dist/site.css');
    }

    public function enqueAdminStylesheets()
    {
    }

    public function enqueScripts()
    {
    }

    public function registerNavigations()
    {
    }

    private function getFiles($dir)
    {
        $themeDir = \get_stylesheet_directory();
        if (!file_exists($themeDir . '/' . $dir)) {
            return array();
        }
        $files = scandir($themeDir . '/' . $dir);

        $files = array_map(function ($file) {
            return basename($file, '.php');
        }, array_filter($files, function ($file) {
            $fileInfo = pathinfo($file);
            return $fileInfo['extension'] === 'php';
        }));

        return $files;
    }

    private function fileToClass($file)
    {
        $file = ucwords($file, '-');
        $file = str_replace('-', '', $file);
        return $file;
    }

    /**
     * Register the post types in app/models automatically
     */
    public function registerPostTypes()
    {
        $modelFiles = $this->getFiles('app/Models');

        foreach ($modelFiles as $modelFile) {
            $modelClass = static::getSiteNamespace().'\\Models\\'.$this->fileToClass($modelFile);

            if (new $modelClass instanceof CustomPostType) {
                $this->registerPostType($modelClass);
            }
        }
    }

    public function isUnderStoryView($viewFile)
    {
        $filePath = \get_stylesheet_directory().'/app/Views/'.$viewFile.'.php';
        if (!file_exists($filePath)) {
            return false;
        }

        $file_contents = file_get_contents($filePath);
        return strpos($file_contents, 'Understory\View') !== false;
    }

    /**
     * Register the views in app/models automatically
     */
    public function registerViews()
    {
        $viewFiles = $this->getFiles('app/Views');

        foreach ($viewFiles as $viewFile) {
            // Make sure the viewFile is a class

            if ($this->isUnderStoryView($viewFile)) {
                $viewClass = static::getSiteNamespace().'\\Views\\'.$this->fileToClass($viewFile);
                $this->registerView($viewClass);
            }
        }
    }

    public function registerTaxonomies()
    {
        $taxonomyFiles = $this->getFiles('app/Taxonomies');

        foreach ($taxonomyFiles as $taxonomyFile) {
            $taxonomyClass = static::getSiteNamespace().'\\Taxonomies\\'.$this->fileToClass($taxonomyFile);

            if (new $taxonomyClass instanceof CustomTaxonomy) {
                $this->registerTaxonomy($taxonomyClass);
            }
        }
    }

    public function registerOptionPages()
    {
    }

    public function customizeAdminMenu()
    {
    }

    public function registerPostType($postTypeClass)
    {
        $postType = new $postTypeClass();
        $postType->register();
    }

    public function registerTaxonomy($taxonomyClass)
    {
        $taxonomy = new $taxonomyClass();
        $taxonomy->register();
    }

    public function registerView($viewClass)
    {
        // Append the full namespace to the classname if it doesn't exist
        if (strpos($viewClass, static::getSiteNameSpace()) === false) {
            $viewClass = static::getSiteNamespace().$viewClass;
        }

        $view = new $viewClass();
        $view->setSite($this);

        // Index the viewClass by its file name, so we can render it
        // when WordPress tries to include that file
        $viewPath = \get_stylesheet_directory().'/app/Views'.$view->getFileName().'.php';
        if (file_exists($viewPath)) {
            $this->views[$viewPath] = $view;
        }

        // View may have a WordPress style filename, like home.php or single-post.php
        // Also on case insensative file systems, we need to make sure both Page.php and page.php load
        // the Page class
        $viewPath = \get_stylesheet_directory().'/app/Views'.$view->getFileNameDashedCase().'.php';
        if (file_exists($viewPath)) {
            $this->views[$viewPath] = $view;
        }

        $view->register();
    }

    /**
     * Called by the WordPress include_template filter, so we can
     * intercept it and render our registered View object instead.
     *
     * This is not a great solution becuase WordPress filters should not
     * cause side effects, but in this case, on render is the only time
     * this filter should be called. Still, we will acknowledge that
     * it is a hack
     *
     * @param  string $template template file
     * @return string           tempalte file, return false if rendering
     */
    public function renderView($template)
    {
        if (array_key_exists($template, $this->views)) {
            echo $this->views[$template]->render();
            return false;
        }

        return $template;
    }

    public function initializeContext($context)
    {
        $context['body_class'] = "site ".$context['body_class'];

        $context['site'] = $this;
        return $context;
    }

    public function setPageTitle($title)
    {
        $this->pageTitle = $title;
    }

    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    public function wpTitle($title)
    {
        global $wp_query;
        if (empty($title)) {
            if ((is_home() || is_front_page())) {
                return $this->name;
            } else {
                $title = $this->getPageTitle();
            }
        }

        return $title . ' | ' . $this->name;
    }

    public function addToTwig($twig)
    {
        /* this is where you can add your own fuctions to twig */
        $twig->addExtension(new \Twig_Extension_StringLoader());
        $twig->addFilter('svg', new \Twig_Filter_Function(array( 'Understory\\Helpers\\Svg', 'embed')));
        return $twig;
    }

    public static function getSiteNamespace()
    {
        $reflection = new \ReflectionClass(get_called_class());
        return $reflection->getNamespaceName();
    }

    public static function getPost($post = null)
    {
        if (!$post) {
            $post = new Post();
            $post->load();
        }

        if (!$post->ID) {
            return null;
        }

        $namespace = static::getSiteNamespace().'\\Models\\';
        $className = $namespace.Inflector::classify($post->post_type);

        if (class_exists($className, false)) {
            return new $className($post);
        }

        return $post;
    }

    /**
     * Wraps Timber::get_posts($query) and intitialzes the each post as the correct
     * CustomPostType class.
     * @param mixed $query
     * @return array
     */
    public static function getPosts($query = false)
    {
        $namespace = $namespace = static::getSiteNamespace().'\\Models\\';

        return array_map(function ($post) use ($namespace) {
            $className = $namespace.$post->post_type;

            if (class_exists($className)) {
                return new $className($post);
            }

            return $post;
        }, Timber::get_posts($query));
    }
}
