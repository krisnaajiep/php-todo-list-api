<?php

spl_autoload_register(function ($class) {
  $file = __DIR__ . '/Helpers/' . $class . '.php';
  if (file_exists($file)) include $file;
});

spl_autoload_register(function ($class) {
  $file = __DIR__ . '/Cores/' . $class . '.php';
  if (file_exists($file)) include $file;
});
