<?php

use \App\UserBot\Parser\BotResponse;

BotResponse::commandAdd('#Неверный формат .+#', 'saveValues');
BotResponse::commandAdd('#Информации о.+ не найдено#', 'saveValues');

BotResponse::commandAdd('#Введите#', 'nextTick');
BotResponse::commandAdd('#Поиск завершён#', 'nextTick');

BotResponse::commandAdd('#(\S+@\S+)+#', 'saveValues', 'Связанные почты');
BotResponse::commandAdd('#(\d+)+#', 'saveValues', 'Связанные телефоны');
BotResponse::commandAdd('#(https:\/\/\S+)+#', 'saveSocials', 'В соц. сетях связан с:');

BotResponse::commandAdd('#Лимит запросов на сегодня исчерпан#', 'changeSession');
