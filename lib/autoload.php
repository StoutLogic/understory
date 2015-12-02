<?php

namespace Understory;

// Register our autoloaders
spl_autoload_register(__NAMESPACE__ . '\\autoload');

function autoload($cls)
{
    $cls = ltrim($cls, '\\');


    if (strpos($cls, __NAMESPACE__) !== 0) {
        return;
    }

    $cls = str_replace(__NAMESPACE__, '', $cls);
    $cls = strtolower(str_replace('_', '', preg_replace('/(?<=\\w)(?=[A-Z])/', "-$1", $cls)));
    $path = dirname(__FILE__)  . str_replace('\\', DIRECTORY_SEPARATOR, $cls) . '.php';

    require_once($path);
}
