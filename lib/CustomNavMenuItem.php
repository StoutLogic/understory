<?php

namespace Understory;

abstract class CustomNavMenuItem extends CustomPostType
{
    static public $name = 'nav_menu_item';

    public function setPost($post = null)
    {
        $this->setMetaDataBinding(new MenuItem($post));
        $this->getConfig();
    }
}
