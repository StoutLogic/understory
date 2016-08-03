<?php

namespace Understory;

class User extends \TimberUser implements MetaDataBinding
{
    public function isLoggedIn()
    {
        if ($this->ID) {
            return true;
        }

        return false;
    }

    /**
     * Implentation of MetaDataBinding::getMetaValue
     *
     * @param  string $metaFieldKey Key for the meta field
     * @return string                Value of the meta field
     */
    public function getMetaValue($key)
    {
        return \get_user_meta($this->ID, $key, true);
    }

    /**
     * Implentation of MetaDataBinding::setMetaValue
     *
     * @param  string $key Key for the meta field
     * @param  string $value Value for the meta field
     */
    public function setMetaValue($key, $value)
    {
        \update_user_meta($this->ID, $key, $value);
    }
}
