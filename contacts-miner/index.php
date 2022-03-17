<?php

require_once './vendor/autoload.php';

require_once './conf/init.php';

use Contacts\Miner\DbHandler;

$db = DbHandler::getInstance();

$contacts_columns = ['email', 'phone'];

$all_inn_data = $db->queryFetchAll("SELECT * FROM inn");

foreach ($all_inn_data as $row) {

    $inn = $row['inn'];
    foreach ($contacts_columns as $column) {
        $values = explode("\n", $row[$column]);

        foreach ($values as $value) {

            if ( $value != null && empty($db->checkExistingValue($inn, $value)) ){
                $db->pushToContactsQueue($inn, $value, $column);
            }
        }

    }
}