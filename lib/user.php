<?php

namespace Understory;

class User extends \TimberUser implements HasMetaData
{
    public function isLoggedIn()
    {
        if ($this->ID) {
            return true;
        }

        return false;
    }

    /**
     * Implentation of HasMetaData->getMetaValue
     *
     * @param  string $metaFieldKey Key for the meta field
     * @return string                Value of the meta field
     */
    public function getMetaValue($key)
    {
        return \get_user_meta($this->ID, $key, true);
    }
    
    /**
     * Implentation of HasMetaData->setMetaValue
     *
     * @param  string $key Key for the meta field
     * @param  string $value Value for the meta field
     */
    public function setMetaValue($key, $value)
    {
        \update_user_meta($this->ID, $key, $value);
    }
}
