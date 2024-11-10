<?php

class Throttle
{
  public function __invoke($min_interval)
  {
    $userIdentifier = $_SERVER['REMOTE_ADDR'];
    $path = "storage/last_request_times.json";

    if (!file_exists($path) || empty(file_get_contents($path)))
      file_put_contents($path, '[]');

    $data = json_decode(file_get_contents($path), true);

    if (array_key_exists($userIdentifier, $data) && microtime(true) - $data[$userIdentifier] < $min_interval) {
      sleep($min_interval);
    } else {
      $data[$userIdentifier] = microtime(true);
      file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }
  }
}
