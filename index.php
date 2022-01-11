<?php

use \App\App;
use App\UserBot\Bot;
use App\UserBot\SessionsHandler;
use danog\MadelineProto\API;

require_once 'vendor/autoload.php';

require_once 'conf/init.php';
require_once 'conf/responses.php';
require_once 'helpers/functions.php';

$app = App::getInstance();
$app->init();

//// Расскомментировать, чтобы добавить новую сессию. Также нужно закомментировать 2 предыдущие строки и прописать название бота
//
//$MadelineProto = new API(TG_SESSIONS_PATH . DIRECTORY_SEPARATOR . 'bot4');
//
//$MadelineProto->start();
//
//$self = $MadelineProto->getSelf();
//$MadelineProto->logger( $self );
//
//$MadelineProto->async(true);
//
//$MadelineProto->loop( function () use ($MadelineProto) {
//    yield $MadelineProto->messages->sendMessage(['peer' => '@kulikovIvan007', 'message' => 'готово']);
//});
