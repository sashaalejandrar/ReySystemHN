<?php
// Configura tu zona horaria para MySQL
date_default_timezone_set('America/Tegucigalpa');

$host = "localhost";
$user = "root"; // Tu usuario de MySQL
$pass = "";     // Tu contraseña de MySQL
$db_name = "tiendasrey"; // El nombre de tu base de datos

$conexion = new mysqli($host, $user, $pass, $db_name);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

?>