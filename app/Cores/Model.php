<?php

class Model
{
  private $stmt, $dbh;

  public function __construct()
  {
    try {
      $this->dbh = new PDO("mysql:host=" . $_ENV['DB_HOST'] . ";", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_PERSISTENT => true]);
      $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      $this->prepare("SHOW DATABASES LIKE :dbname");
      $this->bind(":dbname", $_ENV['DB_DATABASE']);
      if (!$this->single()) $this->createDB($_ENV['DB_DATABASE']);

      $this->exec("USE " . $_ENV['DB_DATABASE']);
    } catch (PDOException $e) {
      die($e->getMessage());
    }
  }

  protected function beginTransaction()
  {
    $this->dbh->beginTransaction();
  }

  protected function commit()
  {
    $this->dbh->commit();
  }

  protected function rollback()
  {
    $this->dbh->rollBack();
  }

  protected function exec(string $query)
  {
    $this->dbh->exec($query);
  }

  protected function prepare(string $query)
  {
    $this->stmt = $this->dbh->prepare($query);
  }

  protected function bind($param, $value, $type = null)
  {
    if (is_null($type)) {
      switch (true) {
        case is_int($value):
          $type = PDO::PARAM_INT;
          break;

        case is_bool($value):
          $type = PDO::PARAM_BOOL;
          break;

        case is_null($value):
          $type = PDO::PARAM_NULL;
          break;

        default:
          $type = PDO::PARAM_STR;
          break;
      }
    }

    $this->stmt->bindValue($param, $value, $type);
  }

  protected function execute()
  {
    $this->stmt->execute();
  }

  protected function lastInsertId(): string|false
  {
    return $this->dbh->lastInsertId();
  }

  protected function resultSet()
  {
    $this->execute();

    return $this->stmt->fetchAll(PDO::FETCH_OBJ);
  }

  protected function single()
  {
    $this->execute();

    return $this->stmt->fetch(PDO::FETCH_OBJ);
  }

  protected function rowCount()
  {
    return $this->stmt->rowCount();
  }

  protected function close()
  {
    $this->dbh = null;
  }

  private function createDB($dbname)
  {
    $this->prepare("CREATE DATABASE $dbname");
    $this->execute();
  }

  protected function statusCode($statusCode): int|false
  {
    return (new StatusCode())($statusCode);
  }
}
