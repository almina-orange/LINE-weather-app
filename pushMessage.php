<?php
// read all library from composer
require_once __DIR__.'/vendor/autoload.php';

// load functions
require __DIR__.'/basic_function.php';

// instancing "CurlHTTPClient" using access token
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));

// instancing "LINEBot" using CurlHTTPClient and secret
$bot = new \LINE\LINEBot($httpClient, ['channelSecret'=>getenv('CHANNEL_SECRET')]);

/*=== main process ===*/
$userId = 'Ucdb9a424655dedcb28b1311f93d5d16a';
$mes = 'Hello Push API';

// push message for userId
$response = $bot->pushMessage($userId, new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($mes));
if (!$response->isSucceeded()) {
  error_log('Failed!'.$response->getHTTPStatus.' '.$response->getRawBody());
}
}
?>
