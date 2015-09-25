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
class Core
{
    private $thisObj;

    /**
     * Create a new instance of Core if not extending Core
     * @param Object $obj should be $this of the object instantiating it.
     */
    public function __construct($obj)
    {
        $this->thisObj = $obj;
    }

    /**
     * Return the correct value of $this
     * @return Object current or passed in value of $this
     */
    private function getThisObj()
    {
        if (isset($this->thisObj)) {
            return $this->thisObj;
        }

        return $this;
    }

    public function __call($field, $args)
    {
        return $this->getThisObj()->__get($field);
    }

    public function __get($field)
    {
        if (method_exists($this->getThisObj(), 'get'.$field)) {
            return $this->getThisObj()->{'get'.$field}();
        } else {
            if (isset( $this->getThisObj()->$field )) {
                return $this->getThisObj()->$field;
            }
            if ($meta_value = $this->getThisObj()->meta($field)) {
                return $this->getThisObj()->$field = $meta_value;
            }
            if (method_exists($this->getThisObj(), $field)) {
                return $this->getThisObj()->$field = $this->getThisObj()->$field();
            }
            return $this->getThisObj()->$field = false;
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
                    $this->getThisObj()->$key = $value;
                } else if (!empty( $key )
                        && !method_exists($this->getThisObj(), $key)
                        && !method_exists($this->getThisObj(), 'get'.$key) ) {
                    $this->getThisObj()->$key = $value;
                }
            }
        }
    }
}
