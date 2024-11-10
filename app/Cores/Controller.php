<?php

class Controller
{
  protected function model(string $model)
  {
    require_once "app/Models/" . $model . ".php";

    return new $model();
  }
}
