<?php

namespace Contacts\Miner;

use App\Db;
use App\Singleton;

class DbHandler extends Db
{
    use Singleton;

    public function __construct()
    {
        parent::__construct();
    }

    public function checkExistingValue( int $inn, string $value): bool|array
    {
        $value = trim($value);
        return $this->queryFetchAll("SELECT id FROM osint_company_contacts WHERE company_inn = $inn AND value LIKE '%$value%'");
    }

    public function pushToContactsQueue( int $inn, string $value, string $type ): void
    {
        $this->pdo->query("INSERT INTO osint_company_contacts (company_inn, value, type) VALUES ($inn, '$value', '$type')");
    }

}