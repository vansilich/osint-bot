<?php

namespace App;

use App\UserBot\Data\Document;
use App\UserBot\Bot;
use App\UserBot\ParseEventHandler;
use App\UserBot\SessionsHandler;
use Exception;

class App
{
    use Singleton;

    public $stack;
    public string $file_path;
    public string $file_name;
    public string $user_nick;

    /**
     * @throws Exception
     */
    public function init()
    {
        if ( empty($_POST) || empty($_FILES) ) {
            return;
        }

        $status_path = ROOT .DIRECTORY_SEPARATOR. 'userbot_status.php';
        $UserBot_status = unserialize( file_get_contents( $status_path ) );
        if ($UserBot_status === USERBOT_RUNNED) {
            die('Сейчас бот занят, попробуйте позже');
        }
        file_put_contents($status_path, serialize(USERBOT_RUNNED));

        $this->file_path = $this->saveFile();
        $this->user_nick = $_POST['nick'];

        Document::getInstance()->init( $this->file_path );

        $SessionsHandler = SessionsHandler::getInstance();
        $SessionsHandler->init();

        $bot = Bot::getInstance();
        $bot->init();

        $this->stack = $bot->parseStart();

        $bot->sendMessage( '/start');

        $this->stack->current();

        $bot->initEventHandler( ParseEventHandler::class );
    }

    private function saveFile(): string
    {
        $info = pathinfo($_FILES['file']['name']);

        $extension = $info['extension'];
        $this->file_name = $_POST['nick'] .".". $extension;

        $target = UPLOADS . DIRECTORY_SEPARATOR . $this->file_name;
        move_uploaded_file($_FILES['file']['tmp_name'], $target);

        return $target;
    }

}