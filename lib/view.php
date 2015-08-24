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
    /**
     * Path of the template. Leave null to auto generate based on namespace and
     * class name.
     * @var string
     */
    private $template = '';

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
        if (empty( $this->template)) {
            $called_class = get_called_class();
            $cls = preg_replace('/.*Views/', '', $called_class);
            $cls = strtolower(str_replace('_', '', preg_replace('/(?<=\\w)(?=[A-Z])/', '-$1', $cls)));
            $this->template = $cls . '.twig';
        }

        return $this->template;
    }
}
