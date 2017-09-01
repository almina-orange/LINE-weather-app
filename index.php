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
// proceed events
foreach ($events as $event) {
  // skip if not MessageEvent Class
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent)) {
    error_log('Non message event has come');
    continue;
  }

  // skip if not TextMessage Class
  // if (!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
  //   error_log('Non text message has come');
  //   continue;
  // }

  // get location from "TextMessage"
  if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {
    $location = $event->getText();
  }
  // get location from "LocationMessage"
  else if ($event instanceof \LINE\LINEBot\Event\MessageEvent\LocationMessage) {
    // replyTextMessage($bot, $event->getReplyToken(), $event->getAddress().'['.$event->getLatitude().','.$event->getLongitude().']');
    // continue;
    // use Google API: Geocoding, and get location by latitude and longitude
    $lat = $event->getLatitude();
    $lng = $event->getLongitude();
    $jsonString = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?language=ja&latlng='.$lat.','.$lng);

    // decode json-string to json-array
    $json = json_decode($jsonString, true);

    // extract location
    $addressComponentArray = $json['results'][0]['address_components'];

    foreach ($addressComponentArray as $addressComponent) {
      // get prefacture
      if (in_array('administrative_area_level_1', $addressComponent['types'])) {
        $prefName = $addressComponent['long_name'];
        break;
      }
    }

    // exception for "Tokyo" and "Osaka"
    if ($prefName == '東京都') {
      $location = '東京';
    } else if ($prefName == '大阪府') {
      $location = '大阪';
    } else {
      foreach ($addressComponentArray as $addressComponent) {
        // get city
        if (in_array('locality', $addressComponent['types']) && !in_array('ward', $addressComponent['types'])) {
          $location = $addressComponent['long_name'];
          break;
        }
      }
    }
  }

  error_log(file_get_contents('php://input'));
  error_log('location: '.var_export($location, true));

  // get user input
  // $location = $event->getText();

  // class parsing XML
  $client = new Goutte\Client();

  // get XML file using "Livedoor Weather API"
  $crawler = $client->request('GET', 'http://weather.livedoor.com/forecast/rss/primary_area.xml');

  // extract city and compare with user input
  foreach ($crawler->filter('channel ldWeather|source pref city') as $city) {
    // if match city name, get location ID
    if (strpos($city->getAttribute('title'), $location) !== false || strpos($city->getAttribute('title').'市', $location) !== false) {
      $locationId = $city->getAttribute('id');
      break;
    }
  }

  // if not match to city
  if (empty($locationId)) {
    // if "LocationMessage", use it
    if ($event instanceof \LINE\LINEBot\Event\MessageEvent\LocationMessage) {
      $location = $prefName;
    }

    // suggestion array
    $suggestArray = array();

    // extract prefacture and compare with user input
    foreach ($crawler->filter('channel ldWeather|source pref') as $pref) {
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
      foreach ($suggestArray as $city) {
        array_push($actionArray, new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder($city, $city));
      }

      // reply button template
      $buttonBuilder = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder('Not found city.', 'perhaps?', null, $actionArray);
      $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder('not found.', $buttonBuilder);

      $bot->replyMessage($event->getReplyToken(), $builder);
    }
    else {
      replyTextMessage($bot, $event->getReplyToken(), 'Not found location. Please city name.');
    }

    // skip after process
    continue;
  }

  // replyTextMessage($bot, $event->getReplyToken(), $location.' location ID is '.$locationId.'.');

  // if get location ID, get weather by json-file
  $jsonString = file_get_contents('http://weather.livedoor.com/forecast/webservice/json/v1?city='.$locationId);
  $json = json_decode($jsonString, true);

  // parse weather updated time
  $date = date_parse_from_format('Y-m-d\TH:i:sP', $json['description']['publicTime']);

  // reply weather and updated time
  if ($json['forecast'][0]['telop'] == '晴れ') {
    // reply sticker of weather, updated time, and sunny
    $updateTimeString = sprintf('%s/%s %s:%s', $date['month'], $date['day'], $date['hour'], $date['minute']);
    $msg1 = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($json['description']['text'].PHP_EOL.PHP_EOL.'Update:'.$updateTimeString);
    $msg2 = new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder(2, 513);
    replyMultiMessage($bot, $event->getReplyToken(), $msg1, $msg2);
  } else if ($json['forecast'][0]['telop'] == '雨') {
    // reply sticker of weather, updated time, and rainy
    $updateTimeString = sprintf('%s/%s %s:%s', $date['month'], $date['day'], $date['hour'], $date['minute']);
    $msg1 = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($json['description']['text'].PHP_EOL.PHP_EOL.'Update:'.$updateTimeString);
    $msg2 = new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder(2, 507);
    replyMultiMessage($bot, $event->getReplyToken(), $msg1, $msg2);
  } else {
    $updateTimeString = sprintf('%s/%s %s:%s', $date['month'], $date['day'], $date['hour'], $date['minute']);
    replyTextMessage($bot, $event->getReplyToken(), $json['description']['text'].PHP_EOL.PHP_EOL.'Update: '.$updateTimeString);
  }
}
?>
