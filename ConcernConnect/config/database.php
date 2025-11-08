<?php
class Database {
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // ✅ Use your actual MySQL connection string
        $database_url = "mysql://root:@127.0.0.1:3306/ticketingsystem";

        // Parse the URL for host, port, username, password, and dbname
        if ($database_url) {
            $parts = parse_url($database_url);
            $this->host = $parts['host'] ?? '127.0.0.1';
            $this->port = $parts['port'] ?? 3306;
            $this->dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
            $this->username = $parts['user'] ?? 'root';
            $this->password = $parts['pass'] ?? '';
        } else {
            // Default fallback values
            $this->host = '127.0.0.1';
            $this->port = 3306;
            $this->dbname = 'ticketingsystem';
            $this->username = 'root';
            $this->password = '';
        }
    }

    public function connect() {
        $this->conn = null;

        try {
            // ✅ Use MySQL instead of PostgreSQL
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);

            // Recommended settings
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
