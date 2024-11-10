<?php

class StatusCode
{
  public function __invoke($responseCode): string|false
  {
    $statusCodes = [201, 400, 401, 400, 403, 404, 409, 429, 500];

    return in_array($responseCode, $statusCodes)
      ? $responseCode
      : false;
  }
}
