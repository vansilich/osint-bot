<?php

namespace App;

class Logger
{

    public static function debug( $msg )
    {
        $date = date('Y-m-d H:m:s', time());
        file_put_contents(TMP . "/debug.log", "\n".$date."\n".$msg."\n", FILE_APPEND);
    }

    public static function error( $msg )
    {
        $date = date('Y-m-d H:m:s', time());
        file_put_contents(TMP . "/error.log", $date."\n".$msg, FILE_APPEND);
    }

}