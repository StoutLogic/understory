<?php
/**
 * Understory View
 *
 * Extend your View with this class to gain Understory View functionality.
 *
 * @package Understory
 */

namespace Understory;

/**
 * Understory View
 */
class View
{
    use Core;

    /**
     * Path of the template. Leave null to auto generate based on namespace and
     * class name.
     * @var string
     */
    private $template = '';
    private $post;
    private $context = array();

    public function __construct()
    {
        
    }

    public function getId()
    {
        return $this->getPost()->ID;
    }

    public function getPost()
    {
        if (!$this->post) {
            $this->setPost(new \TimberPost);
        }

        return $this->post;
    }

    public function setPost($post)
    {
        $this->post = $post;
    }

    public static function getFileName()
    {
        $called_class = get_called_class();
        $cls = preg_replace('/.*Views/i', '', $called_class);

        return str_replace('\\', DIRECTORY_SEPARATOR, $cls);
    }

    /**
     * Generate a Template file path  from the namespace (after \Views) and
     * class name of the view. Be sure to keep your View in the
     * \Views namespace.
     *
     * @return string template file path
     */
    public static function generateTemplateFileName()
    {
        return self::getFileName() . '.twig';
    }

    public static function registerView()
    {
      
    }

    /**
     * Return the template file. It will first check to see if the $template
     * variable is defined, otherwise a template file path will be generated
     * from the namespace (after \Views) and class name of the view. Be sure
     * to keep your View in the \Views namespace.
     *
     * @return string template file path
     */
    public function getTemplate()
    {
        if (empty($this->template)) {
            $this->setTemplate(self::generateTemplateFileName());
        }

        return $this->template;
    }

    /**
     * Manually set the path of the template from the base 'templates' directory.
     * Set to empty string or don't set to autogenerate the template path
     * from the namespace and class name.
     *
     * @param string $template template path
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }


    public function getContext()
    {
        if (!$this->context) {
            $this->context = \Timber::get_context();
        }

        return $this->context;
    }

    public function setContext($key, $value)
    {
        $this->getContext();
        $this->context[$key] = $value;
    }

    public function render()
    {
        \Timber::render($this->getTemplate(), $this->getContext());
    }
}
