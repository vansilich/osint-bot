<?php

namespace App\UserBot;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use App\App;
use App\Singleton;
use App\UserBot\Data\DbHandler;
use danog\MadelineProto\SecurityException;
use function osint\helpers\formatPhone;

class Bot
{
    use Singleton;

    public string $currentColumn = '';
    public string $currentValue = '';
    public int $currentId;

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

    public function initCell(): \Generator
    {

        $DbHandler= DbHandler::getInstance();
        foreach ( $DbHandler->getData() as $row ) {

            $this->currentId = $row['id'];
            foreach ( $DbHandler->wanted_columns as $columnName) {

                $this->currentColumn = $columnName;
                $dataString = $row[$columnName] ?? null;

                foreach ( explode("\n", $dataString) as $value ) {

                    $this->currentValue = $value;

                    if ($columnName === "email" && $value !== '') {

                        $this->waitingEnterCommand = true;
                        yield $this->sendMessage( $this->commands['getEmail'] );
                        $this->waitingEnterCommand = false;
                        yield $this->sendMessage( $value );

                    }
                    elseif ($columnName === "phones") {
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
    }


    public function sendMessage($command): void
    {
        $MadelineProto = SessionsHandler::getInstance()->getSession();
        $this->currentCommand = $command;

        $MadelineProto->loop( function () use ($MadelineProto, $command) {
            yield $MadelineProto->messages->sendMessage(['peer' => BOT_NICK, 'message' => $command], ['queue' => 'bot']);
        });
    }

    public function saveValues($matches): void
    {
        $this->waitingEnterCommand = true;

        DbHandler::getInstance()->saveLastValues($this->currentColumn, $this->currentId, $this->currentValue);

        if ( isset($matches[1]) ) {

            $data = implode("\n", $matches[1]);
            $column = $this->currentColumn === 'email' ? 'phones_osint' : 'email_osint';
            DbHandler::getInstance()->addDataToDB($this->currentId, $column,  $data);
        }
    }

    public function saveSocials($matches) :void
    {

        if ( isset($matches[1]) ) {

            $data = implode("\n", $matches[1]);
            DbHandler::getInstance()->addDataToDB($this->currentId, 'social_osint', $data);
        }
    }

    public function nextTick() :void
    {
        $app = App::getInstance();

        //Next tick of Generator from initCell()
        $app->stack->next();
        $isValid = $app->stack->valid();

        if ( !$isValid ) {

            SessionsHandler::getInstance()->getSession()->stop();
            die();
        }
    }

    /**
     * @throws SecurityException
     */
    public function changeSession(): void
    {
        $SessionsHandler = SessionsHandler::getInstance();

        $SessionsHandler->switchSession($this->currentColumn, $this->currentId, $this->currentValue);
        $MadelineProto = $SessionsHandler->getSession();

        //This part is needed to prevent execution commands from previous session
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
