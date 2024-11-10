<?php

class RateLimiter
{
  public static function attempt(int $limit, int $timeFrame)
  {
    $userIdentifier = $_SERVER['REMOTE_ADDR'];
    $path = "storage/rate-limits.json";

    if (!file_exists($path) || empty(file_get_contents($path)))
      file_put_contents($path, '[]');

    $data = json_decode(file_get_contents($path), true);

    if (array_key_exists($userIdentifier, $data)) {
      $elapseTime = time() - $data[$userIdentifier]['start_time'];

      if ($elapseTime < $timeFrame) {
        if ($data[$userIdentifier]['attempt'] >= $limit) return false;

        $data[$userIdentifier]['attempt']++;
        $data[$userIdentifier]['remaining']--;
      } else {
        $data[$userIdentifier] = self::set($limit, $timeFrame);
      }
    } else {
      $data[$userIdentifier] = self::set($limit, $timeFrame);
    }

    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

    return true;
  }

  private static function set(int $limit, int $timeFrame): array
  {
    return [
      'limit' => $limit,
      'attempt' => 1,
      'start_time' => time(),
      'remaining' => $limit - 1,
      'reset_time' => time() + $timeFrame,
    ];
  }
}
