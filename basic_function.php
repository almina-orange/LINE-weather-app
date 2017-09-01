<?php
/*===== Basic functions =====*/
/*=== basic contents ===*/
// text reply
function replyTextMessage($bot, $replyToken, $text) {
  // reply and get response
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text));

  // if response is storange
  if (!$response->isSucceeded()) {
    // output error
    error_log('Failed! '.$response->getHTTPStatus.' '.$response->getRawBody());
  }
}

// image reply
function replyImageMessage($bot, $replyToken, $originalImageUrl, $previewImageUrl) {
  // reply and get response
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($originalImageUrl, $previewImageUrl));

  // if response is storange
  if (!$response->isSucceeded()) {
    // output error
    error_log('Failed! '.$response->getHTTPStatus.' '.$response->getRawBody());
  }
}

// location reply
function replyLocationMessage($bot, $replyToken, $title, $address, $lat, $lon) {
  // location, longitude, latitude
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder($title, $address, $lat, $lon));

  if (!$response->isSucceeded()) {
    error_log('Failed! '.$response->getHTTPStatus.' '.$response->getRawBody());
  }
}

// sticker reply
function replyStickerMessage($bot, $replyToken, $packageId, $stickerId) {
  // sticker
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder($packageId, $stickerId));

  if (!$response->isSucceeded()) {
    error_log('Failed! '.$response->getHTTPStatus.' '.$response->getRawBody());
  }
}

// video reply
function replyVideoMessage($bot, $replyToken, $originalContentUrl, $previewImageUrl) {
  // video
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\VideoMessageBuilder($originalContentUrl, $previewImageUrl));

  if (!$response->isSucceeded()) {
    error_log('Failed! '.$response->getHTTPStatus.' '.$response->getRawBody());
  }
}

// audio reply
function replyAudioMessage($bot, $replyToken, $originalContentUrl, $audioLength) {
  // audio
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\AudioMessageBuilder($originalContentUrl, $audioLength));

  if (!$response->isSucceeded()) {
    error_log('Failed! '.$response->getHTTPStatus.' '.$response->getRawBody());
  }
}

// multi message reply
function replyMultiMessage($bot, $replyToken, ...$msgs) {
  // instancing "MultiMessageBuilder"
  $builder = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();

  // add all messages in builder
  foreach($msgs as $value) {
    $builder->add($value);
  }

  // multi message
  $response = $bot->replyMessage($replyToken, $builder);

  if (!$response->isSucceeded()) {
    error_log('Failed! '.$response->getHTTPStatus.' '.$response->getRawBody());
  }
}

/*=== rich text contents ===*/
// buttons template reply
function replyButtonsTemplate($bot, $replyToken, $alternativeText, $imageUrl, $title, $text, ...$actions) {
  // action array
  $actionArray = array();

  // add all actions
  foreach ($actions as $value) {
    array_push($actionArray, $value);
  }

  // instancing "ButtonTemplateBuilder" and "TemplateMessageBuilder"
  $buttonBuilder = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder($title, $text, $imageUrl, $actionArray);
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder($alternativeText, $buttonBuilder);

  // button template
  $response = $bot->replyMessage($replyToken, $builder);

  if (!$response->isSucceeded()) {
    error_log('Failed! '.$response->getHTTPStatus.' '.$response->getRawBody());
  }
}

// confirm template reply
function replyConfirmTemplate($bot, $replyToken, $alternativeText, $text, ...$actions) {
  // action array
  $actionArray = array();

  // add all actions
  foreach ($actions as $value) {
    array_push($actionArray, $value);
  }

  // instancing "ConfirmTemplateBuilder" and "TemplateMessageBuilder"
  $confirmBuilder = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder($text, $actionArray);
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder($alternativeText, $confirmBuilder);

  // confirm template
  $response = $bot->replyMessage($replyToken, $builder);

  if (!$response->isSucceeded()) {
    error_log('Failed! '.$response->getHTTPStatus.' '.$response->getRawBody());
  }
}

// carousel template reply
function replyCarouselTemplate($bot, $replyToken, $alternativeText, $columnArray) {
  // instancing "CarouselTemplateBuilder" and "TemplateMessageBuilder"
  $carouselBuilder = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder($columnArray);
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder($alternativeText, $carouselBuilder);

  $response = $bot->replyMessage($replyToken, $builder);

  if (!$response->isSucceeded()) {
    error_log('Failed! '.$response->getHTTPStatus.' '.$response->getRawBody());
  }
}
?>
