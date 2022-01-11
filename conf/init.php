<?php

const DEBUG = 1; //1 - debug on, 0 - production
define("ROOT", dirname(__DIR__));
define("TMP", realpath(ROOT . '/tmp'));
define("CONF", realpath(ROOT . '/conf'));
define("TG_SESSIONS_PATH", realpath(ROOT . '/tg_sessions'));

const STATUS_PATH = ROOT . DIRECTORY_SEPARATOR . 'userbot_status.php';
const LAST_VALUES_PATH = ROOT . DIRECTORY_SEPARATOR . 'last_values.php';

const USERBOT_RUNNED = 'running';
const USERBOT_STOPPED = 'stopped';

if ( !DEBUG ) {
    define('BOT_ID', 1950229897);
    define('BOT_NICK', '@EmailPhoneOSINT_bot');
}
else {
    define('DEBUG_USER_ID', 704977065);
    define('BOT_ID', -640630852);
    define('BOT_NICK', -640630852);
}
