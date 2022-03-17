<?php

namespace App\UserBot;

use App\Logger;
use App\Singleton;
use danog\MadelineProto\API;
use Exception;
use JetBrains\PhpStorm\NoReturn;

class SessionsHandler
{
    use Singleton;

    public int $currentSessionIndex = 0;
    public $self;

    public array $sessions = [];

    protected API $MadelineProto;

    public function __construct() {}

    /**
     * @throws Exception
     */
    public function init()
    {
        $this->setupSessions();
        $sessionID = $this->getSessionID();

        if (!$sessionID){
            throw new Exception('Нет доступных сессий');
        }

        $this->startSession( $sessionID );
    }

    private function setupSessions(): void
    {
        $files = glob(TG_SESSIONS_PATH . '/*');

        foreach($files as $file) {
            $file = pathinfo($file);

            if ( !isset($file['extension']) ) {
                $this->sessions[] = $file['filename'];
            }
        }
    }

    public function getSessionID()
    {
        if (array_key_exists($this->currentSessionIndex, $this->sessions)) {
            return $this->sessions[$this->currentSessionIndex];
        }
        return false;
    }

    protected function startSession( $sessionID )
    {
        Logger::debug("Starting session $sessionID");

        $this->MadelineProto = new API(TG_SESSIONS_PATH . DIRECTORY_SEPARATOR .$sessionID);

        $this->MadelineProto->start();

        $this->self = $this->MadelineProto->getSelf();
        $this->MadelineProto->logger( $this->self );

        $this->MadelineProto->async(true);
    }

    public function getSession(): API
    {
        return $this->MadelineProto;
    }

    public function switchSession()
    {
        $this->currentSessionIndex++;
        $sessionID = $this->getSessionID();

        if (!$sessionID) {
            $this->dieScript();
        }

        $this->MadelineProto->stop();
        $this->startSession($sessionID);
    }

    #[NoReturn] public function dieScript()
    {
        file_put_contents(STATUS_PATH, serialize(USERBOT_STOPPED) );
        $this->MadelineProto->stop();
        die();
    }

}