<?php

namespace App\UserBot;

use App\Logger;
use App\UserBot\Parser\BotResponse;
use danog\MadelineProto\EventHandler;

class ParseEventHandler extends EventHandler
{

    public function getReportPeers(): string
    {
        return '@kulikovIvan007';
    }

    /**
     * Handle updates from supergroups and channels
     *
     * @param array $update Update
     *
     */
    public function onUpdateNewChannelMessage(array $update)
    {
        $this->onUpdateNewMessage( $update );
    }

    /**
     * Handle updates from users.
     *
     * @param array $update Update
     */
    public function onUpdateNewMessage(array $update)
    {

        $content = unserialize( file_get_contents(STATUS_PATH) );

        if ($content === USERBOT_STOPPED) {
            SessionsHandler::getInstance()->dieScript();
        }

        $bot = Bot::getInstance();

        $message = $update['message'];

        try {
            if ( $this->isNotBotWithUser( $message ) ||
                $this->isNotMatchedCommand($message) ||
                $this->wasSentBeforeSession($message) ) {
                return;
            }

        } catch (\Exception $err) {
            Logger::error("\n".$err->getMessage()."\n");
            return;
        }

        Logger::debug("Get message: ${message['message']}");

        list($method, $matches) = BotResponse::commandMatch($message['message']);

        Logger::debug("Do message method: $method");

        $bot->$method( $matches );
    }

    /**
     * Is it not message from bot to user or from user to bot
     *
     * @param $message
     * @return bool
     */
    protected function isNotBotWithUser( $message ): bool
    {
        $from_id = $message['from_id']['user_id'];
        $to_id = $message['to_id']['user_id'] ?? $message['to_id']['chat_id'];
        $currentUserId =  SessionsHandler::getInstance()->self['id'];

        if (!DEBUG) {
            return !( $from_id == BOT_ID || $from_id == $currentUserId ) && ( $to_id == $currentUserId || $to_id == BOT_ID );
        }
        else {
            return !( $from_id == DEBUG_USER_ID || $from_id == $currentUserId ) && ( $to_id == $currentUserId || $to_id == abs(BOT_ID) );
        }
    }

    protected function isNotMatchedCommand( $message ) :bool
    {
        return !isset($message['message']) || !BotResponse::commandMatch($message['message']);
    }

    protected function wasSentBeforeSession($message ) :bool
    {
        return $message['date'] < Bot::getInstance()->timeOfSwitchSession;
    }

}