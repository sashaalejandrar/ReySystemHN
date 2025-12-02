<?php
/**
 * Get Sucursales API
 * Returns branches for a specific business
 */

require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_negocio = intval($_GET['id_negocio'] ?? 0);

if ($id_negocio <= 0) {
    echo json_encode(['error' => 'ID de negocio inválido']);
    exit;
}

$sucursales = getUserSucursales($conexion, $_SESSION['usuario'], $id_negocio);

echo json_encode($sucursales);
?>
