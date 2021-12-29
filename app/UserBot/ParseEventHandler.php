<?php

namespace App\UserBot;

use App\UserBot\Parser\BotResponse;
use danog\MadelineProto\EventHandler;
use function Amp\call;

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
        return $this->onUpdateNewMessage($update);
    }

    /**
     * Handle updates from users.
     *
     * @param array $update Update
     */
    public function onUpdateNewMessage(array $update)
    {
        $file = ROOT . DIRECTORY_SEPARATOR . 'userbot_status.php';
        $content = unserialize(file_get_contents($file));

        if ($content === USERBOT_STOPPED) {
            SessionsHandler::getInstance()->getSession()->stop();
            die();
        }

        $bot = Bot::getInstance();
        $message = $update['message'];
        if ( $this->isNotBotWithUser( $message ) ||
             $this->isNotMatchedCommand($message) ||
             $this->wasSentBeforeSession($message) ) {
            return;
        }

        list($method, $matches) = BotResponse::commandMatch($message['message']);

        if ( $bot->waitingEnterCommand && ($method !== 'nextTick' && $method !== 'changeSession' && $method !== 'saveSocials') ) {
            return;
        }
        if ( !$bot->waitingEnterCommand && ($method !== 'saveValues' && $method !== 'startSending') ) {
            return;
        }

        call([$bot, $method], $matches);
    }

    /**
     * Is it not message from bot to user or from user to bot
     *
     * @param $message
     * @return bool
     */
    protected function isNotBotWithUser( $message ) :bool
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