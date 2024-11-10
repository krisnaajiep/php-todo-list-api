<?php

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class MyJWT
{
  public static function encode(array $data, int $ttl, bool $access): string
  {
    $iat = time();
    $nbf = $iat;
    $exp = $iat + $ttl;

    $payload = [
      'iat' => $iat,
      'nbf' => $nbf,
      'exp' => $exp,
      'jti' => bin2hex(random_bytes(16)),
      'sub' => strval($data['user']['id']),
      'name' => $data['user']['name'],
      'access' => $access,
    ];

    $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

    return $jwt;
  }

  public static function decode(string $jwt): stdClass|false
  {
    try {
      return JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
    } catch (ExpiredException $e) {
      return $e->getPayload();
    } catch (Throwable $e) {
      return false;
    }
  }
}
