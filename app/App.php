<?php

namespace App;

use App\UserBot\Data\DbHandler;
use App\UserBot\Bot;
use App\UserBot\ParseEventHandler;
use App\UserBot\SessionsHandler;
use Exception;

class App
{
    use Singleton;

    public $stack;

    /**
     * @throws Exception
     */
    public function init()
    {

        $UserBot_status = unserialize( file_get_contents( STATUS_PATH ) );

        if ($UserBot_status === USERBOT_RUNNED) {
            die('Сейчас бот занят, попробуйте позже');
        }

        file_put_contents(STATUS_PATH, serialize(USERBOT_RUNNED));

        DbHandler::getInstance()->init();

//        $SessionsHandler = SessionsHandler::getInstance();
//        $SessionsHandler->init();
//
//        $bot = Bot::getInstance();
//        $bot->init();
//
//        $this->stack = $bot->initCell();
//
//        $bot->sendMessage( '/start');
//
//        $this->stack->current();
//
//        $bot->initEventHandler( ParseEventHandler::class );
    }

}