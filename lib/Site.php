<?php

namespace Understory;

use Understory\Config;

class Site extends \TimberSite
{
    protected static $themeSupport = array();
    protected $optionPages = array();
    protected $editor;

    protected $views = array();

    protected $pageTitle;

    public function __construct()
    {
        \Timber::$dirname = 'app/templates';

        self::addThemeSupport();
        
        static::registerComponents();
        static::addEditorConfig();

        \add_filter('timber_context', array( $this, 'initializeContext' ));
        \add_filter('get_twig', array( $this, 'addToTwig' ));

        \add_action('init', array( $this, 'registerOptionPages' ), 10);
        \add_action('init', array( $this, 'registerNavigations' ), 11);
        \add_action('init', array( $this, 'registerTaxonomies' ), 12);
        \add_action('init', array( $this, 'registerPostTypes' ), 13);
        \add_action('init', array( $this, 'registerViews' ), 14);

        \add_action('admin_menu', array( $this, 'customizeAdminMenu' ), 10);

        \add_action('wp_enqueue_scripts', array( $this, 'enqueScripts'), 100);
        \add_action('wp_enqueue_scripts', array( $this, 'enqueDefaultStylesheets'), 100000);
        \add_action('wp_enqueue_scripts', array( $this, 'enqueStylesheets'), 102);
        \add_action('admin_enqueue_scripts', array( $this, 'enqueAdminStylesheets'), 100);

        \add_filter('wp_title', array( $this, 'wpTitle' ));

        \add_filter('template_include', array( $this, 'renderView' ), 1000);

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

    private function getSiteNameSpace()
    {
        $called_class = get_called_class();
        $reflection = new \ReflectionClass($called_class);

        return $reflection->getNamespaceName();
    }

    /**
     * Register the post types in app/models automatically
     */
    public function registerPostTypes()
    {
        $modelFiles = $this->getFiles('app/Models');
        $modelFiles = array_merge($modelFiles, $this->getFiles('app/models'));

        foreach ($modelFiles as $modelFile) {
            $modelClass = $this->getSiteNameSpace().'\\Models\\'.$this->fileToClass($modelFile);
            $modelClass::registerPostType();
        }
    }

    public function isUnderStoryView($viewFile)
    {
        $filePath = \get_stylesheet_directory().'/app/Views/'.$viewFile.'.php';
        if (!file_exists($filePath)) {
            $filePath = \get_stylesheet_directory().'/app/views/'.$viewFile.'.php';
            if (!file_exists($filePath)) {
                return false;
            }
        }

        $file_contents = file_get_contents($filePath);
        return strpos($file_contents, '\Understory\View') !== false;
    }

    /**
     * Register the views in app/models automatically
     */
    public function registerViews()
    {
        $viewFiles = $this->getFiles('app/Views');
        $viewFiles = array_merge($viewFiles, $this->getFiles('app/views'));

        foreach ($viewFiles as $viewFile) {
            // Make sure the viewFile is a class
            if ($this->isUnderStoryView($viewFile)) {
                $viewClass = $this->getSiteNameSpace().'\\Views\\'.$this->fileToClass($viewFile);
                $this->registerView($viewClass);
            }
        }
    }

    public function registerTaxonomies()
    {
       
    }

    public function registerOptionPages()
    {
        
    }

    public function customizeAdminMenu()
    {
        
    }

    public function registerPostType($postTypeClass)
    {
        // Append the full namespace to the classname if it doesn't exist
        if (strpos($postTypeClass, $this->getSiteNameSpace()) === false) {
            $postTypeClass = $this->getSiteNameSpace().'\\'.$postTypeClass;
        }

        $postTypeClass::registerPostType();
    }

    public function registerTaxonomy($taxonomyClass)
    {
        // Append the full namespace to the classname if it doesn't exist
        if (strpos($taxonomyClass, $this->getSiteNameSpace()) === false) {
            $taxonomyClass = $this->getSiteNameSpace().$taxonomyClass;
        }
        
        $taxonomyClass::registerTaxonomy();
    }

    public function registerView($viewClass)
    {
        // Append the full namespace to the classname if it doesn't exist
        if (strpos($viewClass, $this->getSiteNameSpace()) === false) {
            $viewClass = $this->getSiteNameSpace().$viewClass;
        }

        // Index the viewClass by its file name, so we can render it
        // when WordPress tries to include that file
        $viewPath = \get_stylesheet_directory().'/app/Views'.$viewClass::getFileName(false).'.php';
        if (!file_exists($viewPath)) {
            $viewPath = str_replace('app/Views', 'app/views', $viewPath);                    
             if (!file_exists($viewPath)) {
                $viewPath =  \get_stylesheet_directory().'/app/Views'.$viewClass::getFileName().'.php';            
                 if (!file_exists($viewPath)) {
                    $viewPath = str_replace('app/Views', 'app/views', $viewPath);                            
                }
            }
        }
        $this->views[$viewPath] = $viewClass;

        $viewClass::registerView();
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
            $view = new $this->views[$template];
            $view->render();
            $template = "";
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
        if (empty($title)) {
            if (( is_home() || is_front_page() )) {
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
}
