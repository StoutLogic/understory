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
class View implements HasMetaData
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

    /**
     * Implentation of HasMetaData->getMetaValue
     *
     * @param  string $metaFieldKey Key for the meta field
     * @return string                Value of the meta field
     */
    public function getMetaValue($key)
    {
        return \get_post_meta($this->getPost()->ID, $key, true);
    }
    
    /**
     * Implentation of HasMetaData->setMetaValue
     *
     * @param  string $key Key for the meta field
     * @param  string $value Value for the meta field
     */
    public function setMetaValue($key, $value)
    {
        \update_post_meta($this->getPost()->ID, $key, $value);
    }

    /**
     * If a method doesn't exist on the View, delegate to the Post
     * @param  string $field property or method name
     * @return mixed        returned value
     */
    public function __call($method_name, $args)
    {
        if (method_exists($this->getPost(), $method_name)) {
            return call_user_func_array(array($this->getPost(), $method_name), $args);
        } else {
            return $this->__get($method_name);
        }
    }

    /**
     * If a property or method doesn't exist on the View, delegate to the Post
     * @param  string $field property or method name
     * @return mixed        returned value
     */
    public function __get($field)
    {
        return $this->getPost()->$field;
    }
}
