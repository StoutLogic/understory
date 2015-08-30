<?php

namespace Understory;

use Understory\Config;

class Site extends \TimberSite
{
    protected static $themeSupport = array();
    protected $optionPages = array();


    public function __construct()
    {
        $editorConfig = new Config\Editor();

        \Timber::$dirname = 'app/templates';

        self::addThemeSupport();

        add_filter('timber_context', array( $this, 'addToContext' ));
        add_filter('get_twig', array( $this, 'addToTwig' ));

        add_action('init', array( $this, 'registerTaxonomies' ), 10);
        add_action('init', array( $this, 'registerPostTypes' ), 11);
        add_action('init', array( $this, 'registerNavigations' ));
        add_action('init', array( $this, 'registerOptionPages' ));

        add_action('wp_enqueue_scripts', array( $this, 'enqueScripts'), 100);
        add_action('wp_enqueue_scripts', array( $this, 'enqueDefaultStylesheets'), 100000);
        add_action('wp_enqueue_scripts', array( $this, 'enqueStylesheets'), 102);

        parent::__construct();
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
        wp_enqueue_style( 'site', get_template_directory_uri().'/assets/dist/site.css' );
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

    public function addToContext($context)
    {
        $context['body_class'] = "site ".$context['body_class'];
        $context['site'] = $this;
        return $context;
    }

    public function addToTwig($twig)
    {
        /* this is where you can add your own fuctions to twig */
        $twig->addExtension(new \Twig_Extension_StringLoader());
        $twig->addFilter('svg', new \Twig_Filter_Function(array( 'Understory\\Helpers\\Svg', 'embed')));
        return $twig;
    }
}
