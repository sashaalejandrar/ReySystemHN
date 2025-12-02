<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) die("Error de conexión");

$id = $_GET['id'] ?? 0;

$query = "SELECT * FROM pedidos WHERE Id = $id";
$result = $conexion->query($query);
$pedido = $result->fetch_assoc();

if (!$pedido) {
    die("Pedido no encontrado");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Pedido <?=$pedido['Numero_Pedido']?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; }
        }
    </style>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-8">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8 border-b pb-6">
            <div>
                <h1 class="text-3xl font-black text-gray-900">PEDIDO</h1>
                <p class="text-2xl font-bold text-blue-600"><?=$pedido['Numero_Pedido']?></p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600">Fecha de Pedido</p>
                <p class="font-semibold"><?=date('d/m/Y H:i', strtotime($pedido['Fecha_Pedido']))?></p>
                <?php if ($pedido['Fecha_Estimada_Entrega']): ?>
                <p class="text-sm text-gray-600 mt-2">Entrega Estimada</p>
                <p class="font-semibold text-green-600"><?=date('d/m/Y', strtotime($pedido['Fecha_Estimada_Entrega']))?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cliente -->
        <div class="mb-8">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Información del Cliente</h2>
            <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
                <div>
                    <p class="text-sm text-gray-600">Cliente</p>
                    <p class="font-semibold"><?=$pedido['Cliente']?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Teléfono</p>
                    <p class="font-semibold"><?=$pedido['Telefono'] ?: '-'?></p>
                </div>
                <?php if ($pedido['Email']): ?>
                <div class="col-span-2">
                    <p class="text-sm text-gray-600">Email</p>
                    <p class="font-semibold"><?=$pedido['Email']?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Producto -->
        <div class="mb-8">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Detalles del Pedido</h2>
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Producto</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold">Cantidad</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Precio Unit.</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b">
                        <td class="px-4 py-4"><?=$pedido['Producto_Solicitado']?></td>
                        <td class="px-4 py-4 text-center"><?=$pedido['Cantidad']?></td>
                        <td class="px-4 py-4 text-right">L. <?=number_format($pedido['Precio_Estimado'], 2)?></td>
                        <td class="px-4 py-4 text-right font-semibold">L. <?=number_format($pedido['Total_Estimado'], 2)?></td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-4 py-4 text-right font-bold">TOTAL ESTIMADO:</td>
                        <td class="px-4 py-4 text-right text-xl font-black text-blue-600">L. <?=number_format($pedido['Total_Estimado'], 2)?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Estado y Notas -->
        <div class="grid grid-cols-2 gap-6 mb-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Estado del Pedido</h3>
                <span class="inline-block px-4 py-2 rounded-full text-sm font-bold <?php
                    echo match($pedido['Estado']) {
                        'Pendiente' => 'bg-orange-100 text-orange-800',
                        'En Proceso' => 'bg-blue-100 text-blue-800',
                        'Recibido' => 'bg-green-100 text-green-800',
                        'Entregado' => 'bg-purple-100 text-purple-800',
                        'Cancelado' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                ?>"><?=$pedido['Estado']?></span>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Registrado por</h3>
                <p class="font-semibold"><?=$pedido['Usuario_Registro']?></p>
            </div>
        </div>

        <?php if ($pedido['Notas']): ?>
        <div class="mb-8">
            <h3 class="text-sm font-semibold text-gray-600 mb-2">Notas</h3>
            <p class="bg-yellow-50 border-l-4 border-yellow-400 p-4 text-sm"><?=$pedido['Notas']?></p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="border-t pt-6 text-center text-sm text-gray-600">
            <p>Este es un documento generado automáticamente por Rey System</p>
            <p class="mt-2">Para más información, contacte a su proveedor</p>
        </div>

        <!-- Botones -->
        <div class="mt-8 flex gap-4 justify-center no-print">
            <button onclick="window.print()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                🖨️ Imprimir
            </button>
            <button onclick="window.close()" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
                ✖️ Cerrar
            </button>
        </div>
    </div>
</body>
</html>
