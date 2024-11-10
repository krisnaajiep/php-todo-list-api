<?php

require_once __DIR__ . '/vendor/autoload.php';

if (isset($argv[1]) && $argv[1] == 'jwt:secret') {
  $key = bin2hex(random_bytes(32));
  file_put_contents('.env', "JWT_SECRET=$key");
  echo "jwt secret: $key";
  exit;
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/app/init.php';

try {
  if (!isset($_ENV['JWT_SECRET']) || empty($_ENV['JWT_SECRET']))
    throw new Exception("JWT secret key is missing", 500);

  (new Throttle)(1);

  if (!RateLimiter::attempt(60, 60)) throw new Exception("Too many request", 429);

  require_once __DIR__ . '/routes/api.php';

  if (!$route->found) throw new Exception("Not found", 404);
} catch (\Throwable $th) {
  echo Response::json($th->getCode(), ['message' => $th->getMessage()]);
}
