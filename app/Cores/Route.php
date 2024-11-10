<?php

class Route
{
  private $method = "index", $params = [];

  public $found = false;

  private function dispatch(string $url, string $controller, string $method = null)
  {
    if (is_null(Request::get('url'))) return;

    $url = $this->parseURL($url);
    $requestUrl = $this->parseURL(Request::get('url'));

    if (count($url) === count($requestUrl)) {
      foreach ($requestUrl as $key => $value) {
        $param = (is_numeric($value) && $url[$key][0] === '{' && $url[$key][strlen($url[$key]) - 1] === '}');
        if (!$param && $value !== $url[$key]) return;
        if ($param) $id = intval($value);
      }

      require_once 'app/Controllers/' . $controller . '.php';
      $controller = new $controller;

      if (!is_null($method) && method_exists($controller, $method)) $this->method = $method;

      if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'POST') {
        $file = file_get_contents('php://input');
        $raw = json_decode($file, true);

        if (is_null($raw)) {
          if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            parse_str($file, $data);
          } else {
            $data = Request::post();
          }
        } else {
          $data = $raw;
        }

        $this->params = [$data];
      }

      $this->params[] = $id ?? null;

      $this->found = true;

      echo call_user_func_array([$controller, $this->method], $this->params);
    }
  }

  public function get(string $url, string $controller, string $method = null)
  {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') $this->dispatch($url, $controller, $method);
  }

  public function post(string $url, string $controller, string $method = null)
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') $this->dispatch($url, $controller, $method);
  }

  public function put(string $url, string $controller, string $method = null)
  {
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') $this->dispatch($url, $controller, $method);
  }

  public function delete(string $url, string $controller, string $method = null)
  {
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') $this->dispatch($url, $controller, $method);
  }

  private function parseURL(string $url)
  {
    $url = trim($url, "/");
    $url = filter_var($url, FILTER_SANITIZE_URL);
    $url = explode("/", $url);

    return $url;
  }
}
