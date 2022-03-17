<?php

require_once '../vendor/autoload.php';
require_once '../conf/init.php';

$chatInterface = new \Testing\BotChatInterface( 'bot1' );

$messages = $chatInterface->getMessages()['messages'];

foreach ($messages as $message) {
    $res = [];

    $res['peer_id'] = $message['peer_id']['user_id'];
    $res['message'] = $message['message'];
    $res['date'] = date('Y-m-d H:m:s', $message['date']) . " (".$message['date'].")";

    echo '<pre>';
    var_dump( $res );
    echo '</pre>';
}
