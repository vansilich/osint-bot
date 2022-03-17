<?php

namespace App\UserBot;

use App\App;
use App\Logger;
use App\Singleton;
use App\UserBot\Data\DbHandler;
use danog\MadelineProto\SecurityException;
use function osint\helpers\formatPhone;
use function osint\helpers\isEmailValid;

class Bot
{
    use Singleton;

    public int $currentId;
    public int $currentInn;
    public string $currentType = '';
    public string $currentValue = '';

    public int $timeOfSwitchSession = 0;

    public string $currentCommand;

    public function __construct() {}

    public function init()
    {
        $this->timeOfSwitchSession = time();
    }

    /**
     * @throws SecurityException
     */
    public function initEventHandler( $class )
    {
        SessionsHandler::getInstance()->getSession()->startAndLoop($class);
    }

    public function commandsGenerator(): \Generator
    {
        $DbHandler= DbHandler::getInstance();

        foreach ( $DbHandler->getData() as $row ) {

            $this->currentId = $row['id'];
            $this->currentInn = $row['company_inn'];
            $this->currentType = $row['type'];
            $this->currentValue = $value = $row['value'];

            if ($this->currentType === "email" && $value !== '' && isEmailValid( $value )) {

                yield $this->sendCommand( $value );
            }
            elseif ($this->currentType === "phone") {
                $phone = formatPhone( $value );

                if ($phone !== '') {

                    yield $this->sendCommand( $phone );
                }
            }

        }
    }


    public function sendCommand($command): void
    {
        //Задержка для защита от бана за throttling
        sleep( rand(1, 2) );

        $MadelineProto = SessionsHandler::getInstance()->getSession();
        $this->currentCommand = $command;

        Logger::debug("Send: $command");

        $MadelineProto->loop( function () use ($MadelineProto, $command) {
            yield $MadelineProto->messages->sendMessage(['peer' => BOT_NICK, 'message' => $command]);
        });
    }

    public function saveValues( array $matches ): void
    {
        //Задержка для защита от бана за throttling
        sleep( rand(1, 2) );

        $DbHandler = DbHandler::getInstance();

        if ( isset($matches[1]) ) {

            $data = implode("\n", $matches[1]);
            $type = $this->currentType === 'email' ? 'phones_osint' : 'email_osint';
            $DbHandler->saveResults($this->currentInn, $data, $type);
        }

        $DbHandler->markAsFetched( $this->currentId );
    }

    public function saveSocials($matches): void
    {
        if ( isset($matches[1]) ) {

            $data = implode("\n", $matches[1]);
            DbHandler::getInstance()->saveResults($this->currentInn, $data, 'social_osint');
        }
    }

    public function nextTick(): void
    {
        $app = App::getInstance();

        //Next tick of Generator from initCell()
        $app->stack->next();
        $isValid = $app->stack->valid();

        if (!$isValid) {
            SessionsHandler::getInstance()->dieScript();
        }
    }

    /**
     * @throws SecurityException
     */
    public function changeSession(): void
    {
        $SessionsHandler = SessionsHandler::getInstance();

        $SessionsHandler->switchSession();
        $MadelineProto = $SessionsHandler->getSession();

        //This part is needed to prevent execution commands from previous session
        sleep(1);
        $this->timeOfSwitchSession = time();
        sleep(1);

        $MadelineProto->loop( function () use ($MadelineProto) {
            yield $MadelineProto->messages->sendMessage(['peer' => BOT_NICK, 'message' => '/start'], ['queue' => 'bot']);
        });

        $this->sendCommand( $this->currentCommand );

        $this->initEventHandler( ParseEventHandler::class );
    }
}
