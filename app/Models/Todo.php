<?php

class Todo extends Model
{
  private $table = 'todos';

  public function __construct()
  {
    parent::__construct();

    try {
      $this->prepare("SHOW TABLES LIKE :table");
      $this->bind(":table", $this->table);
      if (!$this->single()) $this->createTable();
    } catch (PDOException $e) {
      die("Error: " . $e->getMessage());
    }
  }

  private function createTable()
  {
    $this->prepare("CREATE TABLE $this->table (
                    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT(11) UNSIGNED NOT NULL,
                    title VARCHAR(100) NOT NULL,
                    description TEXT NOT NULL,
                    status ENUM('todo', 'in progress', 'done') DEFAULT 'todo',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                  )");

    $this->execute();
  }

  public function create(array $data)
  {
    $this->beginTransaction();

    try {
      $this->prepare("INSERT INTO $this->table (user_id, title, description) VALUES (:user_id, :title, :description)");
      $this->bind(":user_id", Request::get('user_id'));
      $this->bind(":title", $data["title"]);
      $this->bind(":description", $data["description"]);

      $this->execute();

      $last_id = $this->lastInsertId();

      $this->commit();

      return $this->one($last_id);
    } catch (\PDOException $e) {
      $this->rollback();

      echo Response::json(500, ['message' => $e->getMessage()]);
      exit;
    }
  }

  public function all(int $start = 0, int $limit = 0)
  {
    try {
      $query = "SELECT * FROM $this->table WHERE user_id = :user_id";

      if (!is_null(Request::get('status'))) {
        $status = strtolower(Request::get('status'));
        $query .= " AND status = '$status'";
      }

      if (!is_null(Request::get('order'))) {
        $order = strtoupper(Request::get('order'));
        $query .= " ORDER BY created_at $order";
      }

      $query .= " LIMIT :start, :limit";

      $this->prepare($query);
      $this->bind(':start', $start);
      $this->bind(':limit', $limit);
      $this->bind(':user_id', Request::get('user_id'));

      return $this->resultSet();
    } catch (PDOException $e) {
      echo Response::json(500, ['message' => $e->getMessage()]);
      exit;
    }
  }

  public function count()
  {
    try {
      $this->prepare("SELECT COUNT(*) as total FROM $this->table WHERE user_id = :user_id");
      $this->bind(':user_id', Request::get('user_id'));

      return $this->single()->total;
    } catch (PDOException $e) {
      echo Response::json(500, ['message' => $e->getMessage()]);
      exit;
    }
  }

  public function one(int $id)
  {
    try {
      $this->prepare("SELECT * FROM $this->table WHERE id = :id");
      $this->bind(':id', $id);

      $todo = $this->single();

      if (!$todo) throw new PDOException("Not found", 404);
      if ($todo->user_id !== Request::get('user_id')) throw new PDOException("Forbidden", 403);

      return $todo;
    } catch (PDOException $e) {
      $statusCode = !$this->statusCode($e->getCode()) ? 500 : $e->getCode();

      echo Response::json($statusCode, ['message' => $e->getMessage()]);
      exit;
    }
  }

  public function update(array $data, int $id)
  {
    $this->beginTransaction();

    try {
      $todo = $this->one($id);

      $this->prepare("UPDATE $this->table SET title = :title, description = :description, status = :status WHERE id = :id");
      $this->bind(':title', isset($data['title']) ? $data['title'] : $todo->title);
      $this->bind(':description', isset($data['description']) ? $data['description'] : $todo->description);
      $this->bind(':status', isset($data['status']) ? $data['status'] : $todo->status);
      $this->bind(':id', $id);

      $this->execute();

      $this->commit();

      return $this->one($id);
    } catch (PDOException $e) {
      $this->rollback();

      $statusCode = !$this->statusCode($e->getCode()) ? 500 : $e->getCode();

      echo Response::json($statusCode, ['message' => $e->getMessage()]);
      exit;
    }
  }

  public function delete(int $id)
  {
    try {
      $this->one($id);

      $this->prepare("DELETE FROm $this->table WHERE id = :id");
      $this->bind(':id', $id);

      $this->execute();

      return true;
    } catch (PDOException $e) {
      echo Response::json(500, ['message' => $e->getMessage()]);
      exit;
    }
  }
}
