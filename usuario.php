<?php
// usuario.php - Clase para manejar usuarios
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $Id;
    public $Nombre;
    public $Apellido;
    public $Email;
    public $Celular;
    public $Usuario;
    public $Clave;
    public $Rol;
    public $Perfil;
    public $Ultima_Actividad;
    public $Fecha_Nacimiento;
    public $Fecha_Ingreso;
    public $Cargo;
    public $Estado_Online;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener todos los usuarios con paginación y búsqueda
    public function listarUsuarios($busqueda = "", $limite = 10, $offset = 0, $estado = "") {
        $query = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        
        if (!empty($busqueda)) {
            $busqueda = $this->conn->real_escape_string($busqueda);
            $query .= " AND (Nombre LIKE '%{$busqueda}%' OR Apellido LIKE '%{$busqueda}%' OR Email LIKE '%{$busqueda}%' OR Usuario LIKE '%{$busqueda}%')";
        }
        
        if (!empty($estado)) {
            $estado = $this->conn->real_escape_string($estado);
            $query .= " AND Estado_Online = '{$estado}'";
        }
        
        $query .= " ORDER BY Fecha_Ingreso DESC LIMIT {$limite} OFFSET {$offset}";
        
        $result = $this->conn->query($query);
        return $result;
    }

    // Contar total de usuarios
    public function contarUsuarios($busqueda = "", $estado = "") {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE 1=1";
        
        if (!empty($busqueda)) {
            $busqueda = $this->conn->real_escape_string($busqueda);
            $query .= " AND (Nombre LIKE '%{$busqueda}%' OR Apellido LIKE '%{$busqueda}%' OR Email LIKE '%{$busqueda}%' OR Usuario LIKE '%{$busqueda}%')";
        }
        
        if (!empty($estado)) {
            $estado = $this->conn->real_escape_string($estado);
            $query .= " AND Estado_Online = '{$estado}'";
        }
        
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    // Obtener un usuario por ID
    public function obtenerPorId($id) {
        $id = $this->conn->real_escape_string($id);
        $query = "SELECT * FROM " . $this->table_name . " WHERE Id = '{$id}' LIMIT 1";
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    // Actualizar usuario (sin modificar la clave)
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET Nombre = ?,
                      Apellido = ?,
                      Email = ?,
                      Celular = ?,
                      Usuario = ?,
                      Rol = ?,
                      Perfil = ?,
                      Fecha_Nacimiento = ?,
                      Cargo = ?,
                      Estado_Online = ?
                  WHERE Id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssssssi", 
            $this->Nombre,
            $this->Apellido,
            $this->Email,
            $this->Celular,
            $this->Usuario,
            $this->Rol,
            $this->Perfil,
            $this->Fecha_Nacimiento,
            $this->Cargo,
            $this->Estado_Online,
            $this->Id
        );
        
        return $stmt->execute();
    }

    // Actualizar contraseña
    public function actualizarClave() {
        $query = "UPDATE " . $this->table_name . " 
                  SET Clave = ?
                  WHERE Id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        $clave_hash = password_hash($this->Clave, PASSWORD_DEFAULT);
        $stmt->bind_param("si", $clave_hash, $this->Id);
        
        return $stmt->execute();
    }

    // Cambiar estado online
    public function cambiarEstado($id, $estado) {
        $id = $this->conn->real_escape_string($id);
        $estado = $this->conn->real_escape_string($estado);
        
        $query = "UPDATE " . $this->table_name . " 
                  SET Estado_Online = '{$estado}'
                  WHERE Id = '{$id}'";
        
        return $this->conn->query($query);
    }

    // Actualizar última actividad
    public function actualizarActividad($id) {
        $id = $this->conn->real_escape_string($id);
        $query = "UPDATE " . $this->table_name . " 
                  SET Ultima_Actividad = NOW()
                  WHERE Id = '{$id}'";
        
        return $this->conn->query($query);
    }

    // Eliminar usuario
    public function eliminar($id) {
        $id = $this->conn->real_escape_string($id);
        $query = "DELETE FROM " . $this->table_name . " WHERE Id = '{$id}'";
        return $this->conn->query($query);
    }

    // Verificar si el email ya existe
    public function emailExiste($email, $id_excluir = null) {
        $email = $this->conn->real_escape_string($email);
        $query = "SELECT Id FROM " . $this->table_name . " WHERE Email = '{$email}'";
        
        if ($id_excluir) {
            $id_excluir = $this->conn->real_escape_string($id_excluir);
            $query .= " AND Id != '{$id_excluir}'";
        }
        
        $result = $this->conn->query($query);
        return $result->num_rows > 0;
    }

    // Verificar si el usuario ya existe
    public function usuarioExiste($usuario, $id_excluir = null) {
        $usuario = $this->conn->real_escape_string($usuario);
        $query = "SELECT Id FROM " . $this->table_name . " WHERE Usuario = '{$usuario}'";
        
        if ($id_excluir) {
            $id_excluir = $this->conn->real_escape_string($id_excluir);
            $query .= " AND Id != '{$id_excluir}'";
        }
        
        $result = $this->conn->query($query);
        return $result->num_rows > 0;
    }

    // Obtener tiempo desde última actividad
    public function tiempoDesdeActividad($ultima_actividad) {
        if (empty($ultima_actividad) || $ultima_actividad == '0000-00-00 00:00:00') {
            return "Nunca";
        }
        
        $ahora = time();
        $tiempo = strtotime($ultima_actividad);
        $diferencia = $ahora - $tiempo;
        
        $segundos = $diferencia;
        $minutos = round($diferencia / 60);
        $horas = round($diferencia / 3600);
        $dias = round($diferencia / 86400);
        $semanas = round($diferencia / 604800);
        $meses = round($diferencia / 2629440);
        $anos = round($diferencia / 31553280);
        
        if ($segundos <= 60) {
            return "Justo ahora";
        } else if ($minutos <= 60) {
            return ($minutos == 1) ? "Hace 1 minuto" : "Hace $minutos minutos";
        } else if ($horas <= 24) {
            return ($horas == 1) ? "Hace 1 hora" : "Hace $horas horas";
        } else if ($dias <= 7) {
            return ($dias == 1) ? "Hace 1 día" : "Hace $dias días";
        } else if ($semanas <= 4.3) {
            return ($semanas == 1) ? "Hace 1 semana" : "Hace $semanas semanas";
        } else if ($meses <= 12) {
            return ($meses == 1) ? "Hace 1 mes" : "Hace $meses meses";
        } else {
            return ($anos == 1) ? "Hace 1 año" : "Hace $anos años";
        }
    }
}
?>