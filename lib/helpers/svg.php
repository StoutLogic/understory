<?php

namespace Understory\Helpers;

class Svg
{
    public static function embed($filename)
    {
        return file_get_contents(\get_template_directory().'/assets/dist/img/'.$filename.'.svg');
    }
}
