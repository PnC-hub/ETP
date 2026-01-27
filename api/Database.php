<?php
/**
 * Database Class
 * Handles PDO connection and provides query helpers
 */

class Database {
    private static $instance = null;
    private $connection;

    /**
     * Private constructor - Singleton pattern
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            Response::error('Database connection failed', 500);
        }
    }

    /**
     * Get singleton instance
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute a SELECT query
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return array Result set
     */
    public function select($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Database SELECT Error: ' . $e->getMessage());
            Response::error('Database query failed', 500);
        }
    }

    /**
     * Execute a SELECT query and return single row
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return array|false Single row or false if not found
     */
    public function selectOne($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Database SELECT ONE Error: ' . $e->getMessage());
            Response::error('Database query failed', 500);
        }
    }

    /**
     * Execute an INSERT query
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return int Last inserted ID
     */
    public function insert($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log('Database INSERT Error: ' . $e->getMessage());
            Response::error('Database insert failed', 500);
        }
    }

    /**
     * Execute an UPDATE query
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return int Number of affected rows
     */
    public function update($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Database UPDATE Error: ' . $e->getMessage());
            Response::error('Database update failed', 500);
        }
    }

    /**
     * Execute a DELETE query
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return int Number of affected rows
     */
    public function delete($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Database DELETE Error: ' . $e->getMessage());
            Response::error('Database delete failed', 500);
        }
    }

    /**
     * Execute a custom query (for complex operations)
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Database EXECUTE Error: ' . $e->getMessage());
            Response::error('Database query failed', 500);
        }
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->connection->rollBack();
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
