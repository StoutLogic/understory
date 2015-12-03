<?php

namespace Understory;

/**
 * This class can be extended or delegated to in order to allow twig variables
 * to access values object methods and variables. 
 *
 * If not extending, pass in $this to the constructor so that Core's methods
 * may act upon the correct $this.
 *
 * These functions slightly alter TimberCore's functionality, to allow
 * for getters to be used.
 */
trait Core
{
    public function __call($field, $args)
    {
        return $this->__get($field);
    }

    public function __get($field)
    {
        if (method_exists($this, 'get'.$field)) {
            return $this->{'get'.$field}();
        } else {
            if (isset( $this->$field )) {
                return $this->$field;
            }
            if ($meta_value = $this->meta($field)) {
                return $this->$field = $meta_value;
            }
            if (method_exists($this, $field)) {
                return $this->$field = $this->$field();
            }
            return $this->$field = false;
        }
    }

    function import($info, $force = false)
    {
        if (is_object($info)) {
            $info = get_object_vars($info);
        }
        if (is_array($info)) {
            foreach ($info as $key => $value) {
                if (!empty( $key ) && $force) {
                    $this->$key = $value;
                } else if (!empty( $key )
                        && !method_exists($this, $key)
                        && !method_exists($this, 'get'.$key) ) {
                    $this->$key = $value;
                }
            }
        }
    }
}
