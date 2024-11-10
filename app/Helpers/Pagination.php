<?php

class Pagination
{
  private static $limit, $page = 1, $start = 0, $pages;

  public static function setLimit($limit)
  {
    self::$limit = $limit;
  }

  public static function getLimit(): int
  {
    return self::$limit;
  }

  public static function getStart(): int
  {
    if (!is_null(Request::get('page'))) self::$page = intval(Request::get('page'));
    if (self::$page > 1) self::$start = (self::$page * self::$limit) - self::$limit;

    return self::$start;
  }

  public static function getPages(int $total)
  {
    self::$pages = intval(ceil($total / self::$limit));

    return self::$pages;
  }
}
