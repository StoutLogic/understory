<?php

namespace Understory;

class User extends \TimberUser
{
    public function isLoggedIn()
    {
        if ($this->ID) {
            return true;
        }

        return false;
    }
}
