<?php

namespace App\UserBot;

use App\App;
use App\Singleton;
use App\UserBot\Data\Document;
use danog\MadelineProto\API;
use Exception;

class SessionsHandler
{
    use Singleton;

    protected int $currentSessionIndex = 0;
    public $self;

    protected array $sessions = [
        'bot1',
        'bot2',
        'bot3',
        'bot4',
    ];

    protected API $MadelineProto;

    public function __construct() {}

    /**
     * @throws Exception
     */
    public function init()
    {
        $sessionID = $this->getSessionID();
        if (!$sessionID){
            throw new Exception('Нет доступных сессий');
        }
        $this->startSession( $sessionID );
    }

    protected function getSessionID()
    {
        if (array_key_exists($this->currentSessionIndex, $this->sessions)) {
            return $this->sessions[$this->currentSessionIndex];
        }
        return false;
    }

    protected function startSession( $sessionID )
    {
        $this->MadelineProto = new API(TG_SESSIONS_PATH . DIRECTORY_SEPARATOR .$sessionID);

//        $this->MadelineProto->start();

        $this->self = $this->MadelineProto->getSelf();
        $this->MadelineProto->logger( $this->self );

        $this->MadelineProto->async(true);
    }

    public function getSession(): API
    {
        return $this->MadelineProto;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function switchSession($currentColumn, $currentRowId)
    {
        $this->currentSessionIndex++;
        $sessionID = $this->getSessionID();

        if (!$sessionID) {
            $this->dieScript($currentColumn, $currentRowId);
        }

        $this->MadelineProto->stop();
        $this->startSession($sessionID);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function dieScript($currentColumn, $currentRowId)
    {

        Document::getInstance()->saveLastCell($currentColumn, $currentRowId);

        $app = App::getInstance();

        $this->MadelineProto->loop( function () use ($app) {
            yield $this->MadelineProto->messages->sendMedia([
                'peer' => $app->user_nick,
                'media' => [
                    '_' => 'inputMediaUploadedDocument',
                    'file' => $app->file_path,
                    'attributes' => [
                        [ '_' => 'documentAttributeFilename', 'file_name' => $app->file_name ]
                    ]
                ],
                'message' => 'отработанный файл',
            ]);
        });

        file_put_contents(ROOT . DIRECTORY_SEPARATOR . 'userbot_status.php', serialize(USERBOT_STOPPED));

        die();
    }

}