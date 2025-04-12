<?php

class MainModel
{
    protected $pdo;
    protected $table; // Model içinde belirtilecek

    public function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        $host = getenv('DB_HOST');
        $dbname = getenv('DB_NAME');
        $username = getenv('DB_USER');
        $password = getenv('DB_PASS');
        $charset = getenv('DB_CHARSET');

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function getByVID($vID, $columns = "*")
    {
        $stmt = $this->pdo->prepare("SELECT {$columns} FROM {$this->table} WHERE vID = :vID");
        $stmt->execute(['vID' => $vID]);
        return $stmt->fetch();
    }

    public function getAll($columns = "*")
    {
        $stmt = $this->pdo->query("SELECT {$columns} FROM {$this->table}");
        return $stmt->fetchAll();
    }

    public function getById($id, $columns = "*")
    {
        $stmt = $this->pdo->prepare("SELECT {$columns} FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data, $returnData = true)
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));

        $stmt = $this->pdo->prepare("INSERT INTO {$this->table} ($columns) VALUES ($placeholders)");

        if (!$stmt->execute($data)) {
            return false;
        }
        if ($returnData) {
            $lastId = $this->pdo->lastInsertId();
            return $this->getById($lastId);
        }
        return true;
    }

    public function update($id, array $data, $returnData = true)
    {
        $setClause = implode(", ", array_map(fn($key) => "$key = :$key", array_keys($data)));

        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET $setClause WHERE id = :id");
        $data['id'] = $id;

        if (!$stmt->execute($data)) {
            return false;
        } else if ($stmt->rowCount() === 0) {
            response(NULL, 208);
        }

        return $returnData ? $this->getById($id) : true;
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getWhere(array $conditions, string $columns = "*", $orderBy = null, $limit = null)
    {
        $whereClause = implode(" AND ", array_map(fn($key) => "$key = :$key", array_keys($conditions)));
        $sql = "SELECT {$columns} FROM {$this->table} WHERE $whereClause";

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($conditions);


        return $stmt->fetchAll();
    }

    public function count(array $conditions = [])
    {
        if (empty($conditions)) {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM {$this->table}");
        } else {
            $whereClause = implode(" AND ", array_map(fn($key) => "$key = :$key", array_keys($conditions)));
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE $whereClause";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($conditions);
        }
        return $stmt->fetch()['total'];
    }

    public function exists(array $conditions)
    {
        $whereClause = implode(" AND ", array_map(fn($key) => "$key = :$key", array_keys($conditions)));
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE $whereClause";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($conditions);

        return $stmt->fetch()['count'] > 0;
    }
}
