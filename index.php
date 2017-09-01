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

  error_log(file_get_contents('php://input'));

  /*=== main process ===*/
  // get user input
  $location = $event->getText();

  // class parsing XML
  $client = new Goutte\Client();

  // get XML file using "Livedoor Weather API"
  $crawler = $client->request('GET', 'http://weather.livedoor.com/forecast/rss/primary_area.xml');

  // extract city and compare with user input
  foreach ($crawler->filter('channle ldWeather|source pref city') as $city) {
    // if match city name, get location ID
    if (strpos($city->getAttribute('title'), $location) !== false) {
      $locationId = $city->getAttribute('id');
      break;
    }
  }

  // if not match to city
  if (empty($locationId)) {
    // suggestion array
    $suggestArray = array();

    // extract prefacture and compare with user input
    foreach ($crawler->filter('channle ldWeather|source pref') as $pref) {
      // if match prefacture, get suggested city
      if (strpos($pref->getAttribute('title'), $location) !== false) {
        foreach ($pref->childNodes as $child) {
          if ($child instanceof DOMElement && $child->nodeName == 'city') {
            array_push($suggestArray, $child->getAttribute('title'));
          }
        }
        break;
      }
    }

    // if suggestion exist
    if (count($suggestArray) > 0) {
      // action array
      $actionArray = array();

      // add all suggestions as action
      foreach ($sugegstArray as $city) {
        array_push($actionArray, new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder($city, $city));
      }

      // reply button template
      $buttonBuilder = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder('Not found.', 'perhaps?', null, $actionArray);
      $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder('not found.', $buttonBuilder);

      $bot->replyMessage($event->getReplyToken(), $builder);
    }
    else {
      replyTextMessage($bot, $event->getReplyToken(), 'Not found suggestions. Please city name.');
    }

    // skip after process
    continue;
  }

  replyTextMessage($bot, $event->getReplyToken(), $location.' location ID is '.$locationId.'.');
}
?>
