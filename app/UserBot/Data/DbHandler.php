<?php

namespace App\UserBot\Data;

use App\Db;
use App\Singleton;

class DbHandler
{
    use Singleton;

    public array $wanted_columns = [ 'phones', 'email' ];

    public function init()
    {
        Db::getInstance()->init();
    }

    /**
     * Return to start bot parsing
     *
     * @return array
     */
    public function getData(): array
    {
        $startSettings = unserialize( file_get_contents(LAST_VALUES_PATH) );
        $startFromId = $startSettings['last_id'] ?? 1;

        $response = $this->queryFetchAll( "SELECT id, phones, email FROM inn WHERE id >= $startFromId" );

        if ( $startSettings['last_column'] == 'email' ) {
            unset($response[0]['phones']);
        }

        $currentValues = explode("\n", $response[0][$startSettings['last_column']]);

        $lastValueIndex = array_search( $startSettings['last_value'], $currentValues);

        if ($currentValues[$lastValueIndex] != null) {
            array_splice($currentValues, 0, $lastValueIndex);
        }

        $response[0][ $startSettings['last_column'] ] = implode("\n", $currentValues);

        return $response;
    }

    public function saveLastValues($currentColumn, $currentId, $value)
    {
        file_put_contents(LAST_VALUES_PATH, serialize([
            'last_id' => $currentId,
            'last_column' => $currentColumn,
            'last_value' => $value,
        ]));
    }

    public function addDataToDB($id, $column, $data)
    {
        $pdo = Db::getInstance()->pdo;
        $query = $pdo->query("SELECT $column FROM inn WHERE id = $id");
        $lastValue = $query->fetch(\PDO::FETCH_ASSOC )[$column];

        $data = $lastValue . "\n" . $data;
        $pdo->query("UPDATE inn SET $column = '$data' WHERE id = $id")->execute();
    }

    public function queryFetchAll( $query ): bool|array
    {
        $pdo = Db::getInstance()->pdo;
        $query = $pdo->query( $query );

        return $query->fetchAll( \PDO::FETCH_ASSOC );
    }

}