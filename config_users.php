<?php
// config_users.php - Configuración de base de datos
class Database {
    private $host = "localhost";
    private $db_name = "tiendasrey";  // Cambia esto por tu BD
    private $username = "root";       // Cambia esto por tu usuario
    private $password = "";           // Cambia esto por tu contraseña
    public $conn;

    public function getConnection() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
        
        if ($this->conn->connect_error) {
            error_log("Error de conexión: " . $this->conn->connect_error);
            throw new Exception("Error de conexión a la base de datos");
        }
        
        $this->conn->set_charset("utf8");
        return $this->conn;
    }
}
?>