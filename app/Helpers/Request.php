<?php

class Request
{
  private static function sanitize(string $value): string
  {
    return trim(stripslashes($value));
  }

  public static function post(?string $key = null)
  {
    if ($key === null) {
      $post = [];

      foreach ($_POST as $key => $value) {
        if (is_string($value)) $value = self::sanitize($value);

        $post[$key] = $value;
      }

      return $post;
    }

    if (isset($_POST[$key])) {
      $value = $_POST[$key];

      if (is_string($value)) return self::sanitize($value);

      return $value;
    }

    return null;
  }

  public static function get(?string $key = null)
  {
    if ($key === null) {
      $get = [];

      foreach ($_GET as $key => $value) {
        if (is_string($value)) $value = self::sanitize($value);

        $get[$key] = $value;
      }

      return $get;
    }

    if (isset($_GET[$key])) {
      $value = $_GET[$key];

      if (is_string($value)) return self::sanitize($value);

      return $value;
    }

    return null;
  }
}
