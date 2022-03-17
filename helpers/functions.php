<?php

namespace osint\helpers{

    function formatPhone( string $phone ): string
    {
        preg_match_all('/\d+/', $phone, $matches);
        return implode($matches[0]);
    }

    function isEmailValid( string $email ): bool
    {
        return preg_match_all('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/i', $email);
    }


}