<?php
/**
 * Database Session Handler for Railway
 * Stores sessions in database instead of files
 */

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $pdo;
    private $table = 'sessions';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createTable();
    }
    
    private function createTable() {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS {$this->table} (
                    id VARCHAR(128) PRIMARY KEY,
                    data TEXT,
                    last_activity INT UNSIGNED NOT NULL,
                    INDEX idx_last_activity (last_activity)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch(PDOException $e) {
            error_log("Session table creation error: " . $e->getMessage());
        }
    }
    
    #[\ReturnTypeWillChange]
    public function open($save_path, $session_name) {
        return true;
    }
    
    #[\ReturnTypeWillChange]
    public function close() {
        return true;
    }
    
    #[\ReturnTypeWillChange]
    public function read($session_id) {
        try {
            error_log("SessionHandler - Reading session ID: " . $session_id);
            $stmt = $this->pdo->prepare("SELECT data FROM {$this->table} WHERE id = ? AND last_activity > ?");
            $stmt->execute([$session_id, time() - 3600]); // 1 hour timeout
            $result = $stmt->fetch();
            $data = $result ? $result['data'] : '';
            error_log("SessionHandler - Read result: " . (empty($data) ? 'EMPTY' : 'Found ' . strlen($data) . ' bytes'));
            return $data;
        } catch(PDOException $e) {
            error_log("Session read error: " . $e->getMessage());
            return '';
        }
    }
    
    #[\ReturnTypeWillChange]
    public function write($session_id, $session_data) {
        try {
            error_log("SessionHandler - Writing session ID: " . $session_id);
            error_log("SessionHandler - Session data length: " . strlen($session_data));
            error_log("SessionHandler - Session data preview: " . substr($session_data, 0, 100));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->table} (id, data, last_activity) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE data = ?, last_activity = ?
            ");
            $time = time();
            $result = $stmt->execute([$session_id, $session_data, $time, $session_data, $time]);
            error_log("SessionHandler - Write result: " . ($result ? 'SUCCESS' : 'FAILED'));
            return $result;
        } catch(PDOException $e) {
            error_log("Session write error: " . $e->getMessage());
            return false;
        }
    }
    
    #[\ReturnTypeWillChange]
    public function destroy($session_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
            return $stmt->execute([$session_id]);
        } catch(PDOException $e) {
            error_log("Session destroy error: " . $e->getMessage());
            return false;
        }
    }
    
    #[\ReturnTypeWillChange]
    public function gc($maxlifetime) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE last_activity < ?");
            return $stmt->execute([time() - $maxlifetime]);
        } catch(PDOException $e) {
            error_log("Session gc error: " . $e->getMessage());
            return false;
        }
    }
}

