<?php

namespace App\UserBot\Data;

use App\Db;
use App\Singleton;

class DbHandler extends Db
{
    use Singleton;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return to start bot parsing
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->queryFetchAll( "SELECT id, company_inn, value, type FROM osint_company_contacts WHERE is_fetched != 1" );
    }

    public function saveResults( $inn, $data, $column )
    {
        $query = $this->pdo->query("SELECT $column FROM inn WHERE inn = $inn");
        $lastValue = $query->fetch(\PDO::FETCH_ASSOC )[$column];

        $data = $lastValue . "\n" . $data;
        $this->pdo->query("UPDATE inn SET $column = '$data' WHERE inn = $inn")->execute();
    }

    public function markAsFetched($id)
    {
        $this->pdo->query("UPDATE osint_company_contacts SET is_fetched = 1 WHERE id = $id")->execute();
    }

}