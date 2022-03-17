<?php

namespace Testing;

use danog\MadelineProto\API;
use danog\MadelineProto\messages;

class BotChatInterface
{

    public API $MadelineProto;

    public function __construct(
        public string $sessionName
    )
    {
        $this->MadelineProto = new API(TG_SESSIONS_PATH . '/' . $sessionName);
        $this->MadelineProto->start();
        $this->MadelineProto->async(false);
    }

    public function getMessages()
    {
        return $this->MadelineProto->messages->getHistory([
            'peer' => '@EmailPhoneOSINT_bot',
            'offset_id' => 0,
            'offset_date' => time(),
            'add_offset' => 0,
            'limit' => 500,
            'max_id' => -1,
            'min_id' => -1,
            'hash' => 432043245780,
        ]);
    }
}