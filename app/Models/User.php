<?php

class User extends Model
{
  private $table = "users";

  public function __construct()
  {
    parent::__construct();

    try {
      $this->prepare("SHOW TABLES LIKE :table");
      $this->bind(":table", $this->table);
      if (!$this->single()) $this->createTable();

      $this->prepare("SHOW TABLES LIKE :table");
      $this->bind(":table", 'blacklisted_tokens');
      if (!$this->single()) $this->createBlacklistedTokensTable();
    } catch (PDOException $e) {
      die("Error: " . $e->getMessage());
    }
  }

  private function createTable()
  {
    $this->prepare("CREATE TABLE $this->table (
                    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(50) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE (email)
                  )");

    $this->execute();
  }

  private function createBlacklistedTokensTable()
  {
    $this->prepare("CREATE TABLE blacklisted_tokens (
                    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    jti VARCHAR(255) NOT NULL,
                    user_id INT(11) UNSIGNED NOT NULL,
                    expired_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_jti (jti)
                  )");

    $this->execute();
  }

  public function create(array $data)
  {
    $password = password_hash($data["password"], PASSWORD_DEFAULT);

    $this->beginTransaction();

    try {
      $this->prepare("SELECT COUNT(*) as count FROM $this->table WHERE email = :email");
      $this->bind(":email", $data["email"]);

      if ($this->single()->count > 0)
        throw new PDOException("Email already registered. Please choose a different one.", 409);

      $this->prepare("INSERT INTO $this->table (name, email, password) VALUES (:name, :email, :password)");
      $this->bind(":name", $data["name"]);
      $this->bind(":email", $data["email"]);
      $this->bind(":password", $password);

      $this->execute();

      $last_id = (int)$this->lastInsertId();
      $user = $this->get($last_id);

      $this->commit();

      return $user;
    } catch (PDOException $e) {
      $this->rollback();

      $statusCode = !$this->statusCode($e->getCode()) ? 500 : $e->getCode();

      echo Response::json($statusCode, ['message' => $e->getMessage()]);
      exit;
    }
  }

  public function login(array $data)
  {
    try {
      $this->prepare("SELECT id, name, email, password FROM {$this->table} WHERE BINARY email = :email");
      $this->bind(":email", $data["email"]);

      $this->execute();

      $user = $this->single();

      if ($this->rowCount() === 0 || !password_verify($data["password"], $user->password))
        throw new PDOException("Unauthorized.", 401);

      return $user;
    } catch (PDOException $e) {

      $statusCode = !$this->statusCode($e->getCode()) ? 500 : $e->getCode();

      echo Response::json($statusCode, ['message' => $e->getMessage()]);
      exit;
    }
  }

  public function get(int $id)
  {
    try {
      $this->prepare("SELECT id, name, email FROM {$this->table} WHERE BINARY id = :id");
      $this->bind(":id", $id);

      $this->execute();

      return $this->single();
    } catch (PDOException $e) {
      echo Response::json(500, ['message' => $e->getMessage()]);
      exit;
    }
  }

  public function blacklistToken(array $data)
  {
    $this->beginTransaction();

    try {
      $this->prepare("INSERT INTO blacklisted_tokens (jti, user_id, expired_at) VALUES (:jti, :user_id, :expired_at)");
      $this->bind(':jti', $data['jti']);
      $this->bind(':user_id', $data['user_id']);
      $this->bind(':expired_at', $data['expired_at']);

      $this->execute();

      $this->commit();

      return;
    } catch (PDOException $e) {
      $this->rollback();

      echo Response::json(500, ['message' => $e->getMessage()]);
      exit;
    }
  }

  public function getBlaclistedToken(string $jti)
  {
    $this->prepare("SELECT * FROM blacklisted_tokens WHERE jti = :jti");
    $this->bind(':jti', $jti);

    return $this->single();
  }
}
