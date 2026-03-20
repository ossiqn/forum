<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            die(json_encode(['error' => 'Veritabanı bağlantısı kurulamadı.']));
        }
        $this->connection->set_charset('utf8mb4');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = [], $types = '') {
        if (empty($params)) {
            $result = $this->connection->query($sql);
            return $result;
        }
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) return false;
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }

    public function fetchAll($sql, $params = [], $types = '') {
        $result = $this->query($sql, $params, $types);
        if ($result instanceof mysqli_stmt) {
            $res = $result->get_result();
            return $res->fetch_all(MYSQLI_ASSOC);
        }
        if ($result instanceof mysqli_result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    public function fetchOne($sql, $params = [], $types = '') {
        $result = $this->query($sql, $params, $types);
        if ($result instanceof mysqli_stmt) {
            $res = $result->get_result();
            return $res->fetch_assoc();
        }
        if ($result instanceof mysqli_result) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function insert($sql, $params = [], $types = '') {
        $result = $this->query($sql, $params, $types);
        return $this->connection->insert_id;
    }

    public function lastInsertId() {
        return $this->connection->insert_id;
    }

    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }
}

function db() {
    return Database::getInstance();
}
