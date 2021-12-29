<?php

namespace App\UserBot;

use Amp\Deferred;
use Amp\Loop;
use App\App;
use App\Singleton;
use App\UserBot\Data\Document;
use danog\MadelineProto\SecurityException;
use Exception;
use function osint\helpers\formatPhone;

class Bot
{
    use Singleton;

    public array $executedColumns = [];
    public string $currentColumn = '';
    public int $currentRowId;

    public int $timeOfSwitchSession = 0;
    public bool $waitingEnterCommand = false;

    public string $currentCommand;

    private array $commands = [
        'getEmail' => '📧 Поиск по почте',
        'getPhone' => '📞 Поиск по номеру',
    ];

    public function __construct() {}

    public function init()
    {
        $this->timeOfSwitchSession = time();
//        var_dump($this->MadelineProto->getInfo(-342899624));
    }

    /**
     * @throws SecurityException
     */
    public function initEventHandler( $class )
    {
        SessionsHandler::getInstance()->getSession()->startAndLoop($class);
    }

    /**
     * @throws Exception
     */
    public function parseStart(): \Generator
    {
        foreach (Document::getInstance()->iterator() as $chunk) {
            foreach ( $chunk as $key => $row ) {
                $this->currentRowId = $key;

                yield $this->initCell( $row );
            }
        }
    }

    private function initCell( $row ): \Generator
    {

        $documentSheet = Document::getInstance();

        foreach ( $documentSheet->wanted_columns as $columnName => $column) {
            $this->currentColumn = $columnName;

            $dataString = $row[$column['columnIndex']];

            foreach ( explode("\n", $dataString) as $value ) {
                if ($value === 'break') {
                    SessionsHandler::getInstance()->dieScript($this->currentColumn, $this->currentRowId);
                }
                if ($columnName === "email" && $value !== '') {

                    $this->waitingEnterCommand = true;
                    yield $this->sendMessage( $this->commands['getEmail'] );
                    $this->waitingEnterCommand = false;
                    yield $this->sendMessage( $value );
                }
                elseif ($columnName === "phone") {
                    $phone = formatPhone($value);

                    if ($phone !== '') {
                        $this->waitingEnterCommand = true;
                        yield $this->sendMessage( $this->commands['getPhone'] );
                        $this->waitingEnterCommand = false;
                        yield $this->sendMessage( $phone );
                    }
                }
            }

        }
    }

    public function sendMessage($command) :void
    {
        $MadelineProto = SessionsHandler::getInstance()->getSession();

        $this->currentCommand = $command;
        $MadelineProto->loop( function () use ($MadelineProto, $command) {
            yield $MadelineProto->messages->sendMessage(['peer' => BOT_NICK, 'message' => $command], ['queue' => 'bot']);
        });
    }

    public function startSending() :void
    {
        $app = App::getInstance();

        $this->executedColumns = [];

        //start loop Generator from initCell()
        $app->stack->current()->current();
    }

    public function saveValues($matches): \Amp\Promise
    {
        $this->waitingEnterCommand = true;

        $deferred = new Deferred();

        Loop::delay(10000, function () use ($deferred, $matches) {
            if ( !$this->currentRowId && !$this->currentColumn ) {
                return;
            }

            $data = '';
            if ( isset($matches[1]) ) {
                $data = implode("\n", $matches[1]);
            }

            Document::getInstance()->saveToSheet($data, $this->currentRowId, $this->currentColumn);
            $this->nextTick();

            $deferred->resolve();
        });

        return $deferred->promise();
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function saveSocials($matches) :void
    {
        if (!$this->currentRowId) {
            return;
        }

        $data = '';
        if ( isset($matches[1]) ) {
            $data = implode("\n", $matches[1]);
        }
        Document::getInstance()->saveSocialsToSheet($data, $this->currentRowId);
    }

    public function nextTick() :void
    {
        $app = App::getInstance();

        $isValidBefore = $app->stack->current()->valid();
        //Next tick of Generator from initCell()
        $app->stack->current()->next();
        $isValidAfter = $app->stack->current()->valid();

        if ( $isValidBefore && !$isValidAfter ){
            //Next tick of Generator from parseStart()
            $app->stack->next();
            if ( !$app->stack->valid() ){
                SessionsHandler::getInstance()->dieScript($this->currentColumn, $this->currentRowId);
            }
            $this->startSending();
        }
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws SecurityException
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function changeSession() :void
    {
        $SessionsHandler = SessionsHandler::getInstance();

        $SessionsHandler->switchSession($this->currentColumn, $this->currentRowId);
        $MadelineProto = $SessionsHandler->getSession();

        //This part needed to prevent execution commands from another session
        sleep(1);
        $this->timeOfSwitchSession = time();
        sleep(1);

        $MadelineProto->loop( function () use ($MadelineProto) {
            yield $MadelineProto->messages->sendMessage(['peer' => BOT_NICK, 'message' => '123'], ['queue' => 'bot']);
        });

        $this->sendMessage( $this->currentCommand );
        $this->initEventHandler( ParseEventHandler::class );
    }
}
