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

// proceed events
foreach ($events as $event) {
  // skip if not MessageEvent Class
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent)) {
    error_log('Non message event has come');
    continue;
  }

  // skip if not TextMessage Class
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
    error_log('Non text message has come');
    continue;
  }

  // get user input
  $location = $event->getText();

  // class parsing XML
  $client = new Goutte\Client();

  // get XML file using "Livedoor Weather API"
  $crawler = $client->request('GET', 'http://weather.livedoor.com/forecast/rss/primary_area.xml');

  error_log(var_export($crawler, true));

  // extracting city name and compare with user input
  foreach ($crawler->filter('channle ldWeather|source pref city') as $city) {
    // if match, get location ID
    if ($city->getAttribute('title') == $location || $city->getAttribute('title')."市" == $location) {
      $locationId = $city->getAttribute('id');
      break;
    }
  }
}
?>
