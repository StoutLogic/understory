<?php

namespace Understory;

use Timber;

class MenuItem extends Timber\MenuItem implements MetaDataBinding
{
    public function getBindingName()
    {
        return $this->post_type;
    }

    /**
     * Implentation of MetaDataBinding::getMetaValue
     *
     * @param  string $key Key for the meta field
     * @return string Value of the meta field
     */
    public function getMetaValue($key)
    {
        return \get_post_meta($this->ID, $key, true);
    }

    /**
     * Implentation of MetaDataBinding::setMetaValue
     *
     * @param  string $key Key for the meta field
     * @param  string $value Value for the meta field
     */
    public function setMetaValue($key, $value)
    {
        \update_post_meta($this->ID, $key, $value);
    }

    /**
     * Order of method calls:
     *
     * 1. getMethodName($args)
     * 2. methodName($args)
     * 3. propertyName
     * 4. fall back to Timber's core implementation
     *
     * @param string $propertyName
     * @param array $args
     * @return mixed
     */
    public function __call($propertyName, $args = [])
    {
        if (method_exists($this, 'get'.$propertyName)) {
            return call_user_func_array([$this, 'get'.$propertyName], $args);
        } else if (method_exists($this, $propertyName)) {
            return call_user_func_array([$this, $propertyName], $args);
        } else if (property_exists($this, $propertyName)) {
            return $this->$propertyName;
        }

        return parent::__call($propertyName, $args);
    }
}
