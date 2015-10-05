<?php

namespace Understory;

use Understory\Config;

class Site extends \TimberSite
{
    protected static $themeSupport = array();
    protected $optionPages = array();
    protected $editor;

    public function __construct()
    {
        \Timber::$dirname = 'app/templates';

        self::addThemeSupport();
        
        static::registerComponents();
        static::addEditorConfig();

        \add_filter('timber_context', array( $this, 'initializeContext' ));
        \add_filter('get_twig', array( $this, 'addToTwig' ));

        \add_action('init', array( $this, 'registerTaxonomies' ), 10);
        \add_action('init', array( $this, 'registerPostTypes' ), 11);
        \add_action('init', array( $this, 'registerNavigations' ));
        \add_action('init', array( $this, 'registerOptionPages' ));

        \add_action('admin_menu', array( $this, 'customizeAdminMenu' ), 10);

        \add_action('wp_enqueue_scripts', array( $this, 'enqueScripts'), 100);
        \add_action('wp_enqueue_scripts', array( $this, 'enqueDefaultStylesheets'), 100000);
        \add_action('wp_enqueue_scripts', array( $this, 'enqueStylesheets'), 102);
        \add_action('admin_enqueue_scripts', array( $this, 'enqueAdminStylesheets'), 100);

        \add_filter( 'wp_title', array( $this, 'wpTitle' ) );

        // Warm custom template cache
        \get_page_templates();

        parent::__construct();
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
        \wp_enqueue_style('site', get_template_directory_uri().'/assets/dist/site.css');
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

    public function registerPostTypes()
    {
        
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

    public function initializeContext($context)
    {
        $context['body_class'] = "site ".$context['body_class'];
        $context['site'] = $this;
        return $context;
    }

    function wpTitle( $title )
    {
      if( empty( $title ) && ( is_home() || is_front_page() ) ) {
        return $this->name;
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
