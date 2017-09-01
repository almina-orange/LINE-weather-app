<?php
// read all library from composer
require_once __DIR__.'/vendor/autoload.php';

// load functions
require __DIR__.'/basic_function.php';

// instancing "CurlHTTPClient" using access token
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));

// instancing "LINEBot" using CurlHTTPClient and secret
$bot = new \LINE\LINEBot($httpClient, ['channelSecret'=>getenv('CHANNEL_SECRET')]);

// get signature of LINE Messaging API
$signature = $_SERVER['HTTP_'.\LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

// check signature, and parse and storage request
// if uncorrectly, preview exception
try {
  $events = $bot->parseEventRequest(file_get_contents('php://input'), $signature);
} catch (\LINE\LINEBot\Exception\InvalidSignatureException $e) {
  error_log('parseEventRequest failed. InvalidSignatureException => '.var_export($e, true));
} catch (\LINE\LINEBot\Exception\UnknownEventTypeException $e) {
  error_log('parseEventRequest failed. UnknownEventTypeException => '.var_export($e, true));
} catch (\LINE\LINEBot\Exception\UnknownMessageTypeException $e) {
  error_log('parseEventRequest failed. UnknownMessageTypeException => '.var_export($e, true));
} catch (\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
  error_log('parseEventRequest failed. InvalidEventRequestException => '.var_export($e, true));
}

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
