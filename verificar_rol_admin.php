<?php
// Script para verificar y cambiar rol de usuario a admin
session_start();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener usuario actual
$usuario_actual = $_SESSION['usuario'] ?? 'No hay sesión';

echo "<h2>Información del Usuario Actual</h2>";
echo "<p>Usuario en sesión: <strong>$usuario_actual</strong></p>";

if ($usuario_actual !== 'No hay sesión') {
    // Consultar rol actual
    $stmt = $conexion->prepare("SELECT Usuario, Nombre, Apellido, Rol FROM usuarios WHERE Usuario = ?");
    $stmt->bind_param("s", $usuario_actual);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<h3>Datos Actuales:</h3>";
        echo "<ul>";
        echo "<li>Nombre: {$user['Nombre']} {$user['Apellido']}</li>";
        echo "<li>Rol Actual: <strong>{$user['Rol']}</strong></li>";
        echo "</ul>";
        
        if ($user['Rol'] !== 'admin') {
            echo "<p style='color: orange;'>⚠️ Tu usuario NO es admin. Los botones de acción no serán visibles.</p>";
            echo "<h3>¿Cambiar a Admin?</h3>";
            echo "<form method='POST'>";
            echo "<button type='submit' name='cambiar_admin' style='padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;'>✅ Cambiar mi usuario a ADMIN</button>";
            echo "</form>";
        } else {
            echo "<p style='color: green;'>✅ Tu usuario YA es admin. Los botones de acción deberían ser visibles.</p>";
        }
    }
    $stmt->close();
}

// Procesar cambio de rol
if (isset($_POST['cambiar_admin']) && $usuario_actual !== 'No hay sesión') {
    $stmt = $conexion->prepare("UPDATE usuarios SET Rol = 'admin' WHERE Usuario = ?");
    $stmt->bind_param("s", $usuario_actual);
    
    if ($stmt->execute()) {
        echo "<p style='color: green; font-weight: bold; margin-top: 20px;'>✅ ¡Rol cambiado a ADMIN exitosamente!</p>";
        echo "<p>Recarga la página de reportes_caja.php para ver los botones de acción.</p>";
    } else {
        echo "<p style='color: red;'>❌ Error al cambiar el rol</p>";
    }
    $stmt->close();
}

// Mostrar todos los usuarios
echo "<hr>";
echo "<h3>Todos los Usuarios del Sistema:</h3>";
$result = $conexion->query("SELECT Usuario, Nombre, Apellido, Rol FROM usuarios ORDER BY Rol DESC, Nombre");

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f3f4f6;'>";
echo "<th>Usuario</th><th>Nombre Completo</th><th>Rol</th>";
echo "</tr>";

while ($row = $result->fetch_assoc()) {
    $bg = $row['Rol'] === 'admin' ? 'background: #d1fae5;' : '';
    echo "<tr style='$bg'>";
    echo "<td>{$row['Usuario']}</td>";
    echo "<td>{$row['Nombre']} {$row['Apellido']}</td>";
    echo "<td><strong>{$row['Rol']}</strong></td>";
    echo "</tr>";
}

echo "</table>";

$conexion->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f9fafb;
    }
    h2, h3 {
        color: #1f2937;
    }
</style>
