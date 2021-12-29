<?php

namespace App\UserBot\Parser;

class BotResponse
{

    private static array $commands = [];

    public static function commandAdd( $regexp, $command, $needle = null )
    {
        self::$commands[$regexp] = ['command' => $command, 'needle' => $needle];
    }

    public static function commandMatch($text): bool|array
    {
        foreach (self::$commands as $regexp => $arr) {
            if ( preg_match_all($regexp.'mu', $text, $matches) ) {
                if (!$arr['needle'] || strripos($text, $arr['needle']) !== false) {
                    return [self::$commands[$regexp]['command'], $matches];
                }
            }
        }
        return false;
    }

}