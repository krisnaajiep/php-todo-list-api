<?php

class Response
{
  public static function json(int $code = 200, array $data = [])
  {
    http_response_code($code);
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset");
    header('Content-type:application/json');

    if ($code !== 500 && $code !== 429) {
      $userIdentifier = $_SERVER['REMOTE_ADDR'];
      $path = "storage/rate-limits.json";
      $rateLimit = json_decode(file_get_contents($path), true);
      $limit = $rateLimit[$userIdentifier]['limit'];
      $remaining = $rateLimit[$userIdentifier]['remaining'];
      $reset_time = $rateLimit[$userIdentifier]['reset_time'];

      header('X-RateLimit-Limit:' . $limit);
      header('X-RateLimit-Remaining:' . $remaining);
      header('X-RateLimit-Reset:' . $reset_time);
    }

    return json_encode($data);
  }
}
