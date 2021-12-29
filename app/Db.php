<?php

namespace App;

class Db
{
    use Singleton;

    public \PDO $pdo;

    public function __construct() {}

    public function init() {
        $params = require_once CONF . DIRECTORY_SEPARATOR . 'db.php';

        $this->pdo = new \PDO("mysql:host=${params['DB_HOST']};port=${params['DB_PORT']};dbname=${params['DB_NAME']}",
            $params['DB_USER'], $params['DB_PASSWORD']) or die('Не удалось подключиться к базе данных');
    }

}