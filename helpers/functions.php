<?php

namespace osint\helpers{

    function formatPhone( string $phone ): string
    {
        preg_match_all('/\d+/', $phone, $matches);
        return implode($matches[0]);
    }
}