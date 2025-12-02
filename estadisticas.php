<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'funciones.php';
date_default_timezone_set('America/Tegucigalpa');

VerificarSiUsuarioYaInicioSesion();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener información del usuario
$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Usuario = $row['Usuario'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Email = $row['Email'];
    $Celular = $row['Celular'];
    $Perfil = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);

// ============================================
// MÉTRICAS GENERALES
// ============================================

// Ventas
$total_ventas = $conexion->query("SELECT SUM(Total) as total FROM ventas")->fetch_assoc()['total'] ?? 0;
$ventas_hoy = $conexion->query("SELECT SUM(Total) as total FROM ventas WHERE DATE(Fecha_Venta) = CURDATE()")->fetch_assoc()['total'] ?? 0;
$ventas_mes = $conexion->query("SELECT SUM(Total) as total FROM ventas WHERE MONTH(Fecha_Venta) = MONTH(CURDATE()) AND YEAR(Fecha_Venta) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;
$num_ventas = $conexion->query("SELECT COUNT(*) as total FROM ventas")->fetch_assoc()['total'];

// Clientes
$total_clientes = $conexion->query("SELECT COUNT(*) as total FROM clientes")->fetch_assoc()['total'];
$clientes_mes = $conexion->query("SELECT COUNT(*) as total FROM clientes WHERE MONTH(Fecha_Registro) = MONTH(CURDATE()) AND YEAR(Fecha_Registro) = YEAR(CURDATE())")->fetch_assoc()['total'];

// Productos
$total_productos = $conexion->query("SELECT COUNT(*) as total FROM stock")->fetch_assoc()['total'];
$productos_bajo_stock = $conexion->query("SELECT COUNT(*) as total FROM stock WHERE Stock < 10")->fetch_assoc()['total'];

// Deudas
$total_deudas = $conexion->query("SELECT SUM(monto) as total FROM deudas WHERE estado = 'Pendiente'")->fetch_assoc()['total'] ?? 0;
$num_deudas = $conexion->query("SELECT COUNT(*) as total FROM deudas WHERE estado = 'Pendiente'")->fetch_assoc()['total'];

// ============================================
// DATOS PARA GRÁFICOS
// ============================================

// Ventas por día (últimos 7 días)
$ventas_7dias = [];
$labels_7dias = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $total = $conexion->query("SELECT SUM(Total) as total FROM ventas WHERE DATE(Fecha_Venta) = '$fecha'")->fetch_assoc()['total'] ?? 0;
    $ventas_7dias[] = floatval($total);
    $labels_7dias[] = date('d/m', strtotime($fecha));
}

// Ventas por mes (últimos 6 meses)
$ventas_6meses = [];
$labels_6meses = [];
for ($i = 5; $i >= 0; $i--) {
    $fecha = date('Y-m', strtotime("-$i months"));
    $total = $conexion->query("SELECT SUM(Total) as total FROM ventas WHERE DATE_FORMAT(Fecha_Venta, '%Y-%m') = '$fecha'")->fetch_assoc()['total'] ?? 0;
    $ventas_6meses[] = floatval($total);
    $labels_6meses[] = date('M Y', strtotime($fecha . '-01'));
}

// Top 5 productos más vendidos
$top_productos = $conexion->query("SELECT Producto_Vendido, SUM(Cantidad) as total FROM ventas GROUP BY Producto_Vendido ORDER BY total DESC LIMIT 20");
$productos_nombres = [];
$productos_cantidades = [];
while ($row = $top_productos->fetch_assoc()) {
    $productos_nombres[] = $row['Producto_Vendido'];
    $productos_cantidades[] = intval($row['total']);
}

// Métodos de pago
$metodos_pago = $conexion->query("SELECT MetodoPago, COUNT(*) as total FROM ventas GROUP BY MetodoPago");
$metodos_nombres = [];
$metodos_valores = [];
while ($row = $metodos_pago->fetch_assoc()) {
    $metodos_nombres[] = $row['MetodoPago'] ?: 'No especificado';
    $metodos_valores[] = intval($row['total']);
}

// Top 5 clientes que más compran
$top_clientes = $conexion->query("SELECT Cliente, SUM(Total) as total FROM ventas GROUP BY Cliente ORDER BY total DESC LIMIT 5");
$clientes_nombres = [];
$clientes_totales = [];
while ($row = $top_clientes->fetch_assoc()) {
    $clientes_nombres[] = $row['Cliente'];
    $clientes_totales[] = floatval($row['total']);
}

// Ventas por vendedor
$ventas_vendedor = $conexion->query("SELECT Vendedor, COUNT(*) as total FROM ventas GROUP BY Vendedor ORDER BY total DESC LIMIT 5");
$vendedores_nombres = [];
$vendedores_ventas = [];
while ($row = $ventas_vendedor->fetch_assoc()) {
    $vendedores_nombres[] = $row['Vendedor'];
    $vendedores_ventas[] = intval($row['total']);
}

// Estadísticas adicionales
$ticket_promedio = $num_ventas > 0 ? ($total_ventas / $num_ventas) : 0;
$ventas_semana = $conexion->query("SELECT SUM(Total) as total FROM ventas WHERE YEARWEEK(Fecha_Venta, 1) = YEARWEEK(CURDATE(), 1)")->fetch_assoc()['total'] ?? 0;
$total_usuarios = $conexion->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Estadísticas - Rey System</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "primary": "#137fec",
            },
            fontFamily: {
                "display": ["Inter", "sans-serif"]
            }
        }
    }
}
</script>
<style>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
</head>
<body class="bg-[#f6f7f8] dark:bg-[#101922] font-display">

<div class="flex min-h-screen w-full">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 p-8">
<div class="mx-auto max-w-7xl">

<!-- Header -->
<div class="mb-8">
    <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">Estadísticas del Sistema</h1>
    <p class="text-gray-500 dark:text-[#92a4c9] text-base mt-2">Análisis completo del rendimiento de tu negocio</p>
</div>

<!-- Métricas principales -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Ventas Totales -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <span class="material-symbols-outlined text-4xl opacity-80">payments</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Ventas Totales</p>
                <p class="text-3xl font-bold">L<?php echo number_format($total_ventas, 2); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-sm opacity-90">
            <span class="material-symbols-outlined text-base">trending_up</span>
            <span><?php echo number_format($num_ventas); ?> transacciones</span>
        </div>
    </div>

    <!-- Clientes -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <span class="material-symbols-outlined text-4xl opacity-80">group</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Total Clientes</p>
                <p class="text-3xl font-bold"><?php echo number_format($total_clientes); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-sm opacity-90">
            <span class="material-symbols-outlined text-base">person_add</span>
            <span>+<?php echo $clientes_mes; ?> este mes</span>
        </div>
    </div>

    <!-- Productos -->
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <span class="material-symbols-outlined text-4xl opacity-80">inventory_2</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Productos</p>
                <p class="text-3xl font-bold"><?php echo number_format($total_productos); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-sm opacity-90">
            <span class="material-symbols-outlined text-base">warning</span>
            <span><?php echo $productos_bajo_stock; ?> bajo stock</span>
        </div>
    </div>

    <!-- Deudas -->
    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <span class="material-symbols-outlined text-4xl opacity-80">account_balance_wallet</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Deudas Pendientes</p>
                <p class="text-3xl font-bold">L<?php echo number_format($total_deudas, 2); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-sm opacity-90">
            <span class="material-symbols-outlined text-base">receipt_long</span>
            <span><?php echo $num_deudas; ?> créditos activos</span>
        </div>
    </div>
</div>

<!-- Tarjetas de ventas -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center gap-3 mb-2">
            <span class="material-symbols-outlined text-primary">today</span>
            <h3 class="text-gray-900 dark:text-white text-lg font-bold">Ventas de Hoy</h3>
        </div>
        <p class="text-gray-900 dark:text-white text-4xl font-black">L<?php echo number_format($ventas_hoy, 2); ?></p>
        <p class="text-gray-500 dark:text-[#92a4c9] text-sm mt-2">Ingresos del día actual</p>
    </div>

    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center gap-3 mb-2">
            <span class="material-symbols-outlined text-primary">date_range</span>
            <h3 class="text-gray-900 dark:text-white text-lg font-bold">Ventas de la Semana</h3>
        </div>
        <p class="text-gray-900 dark:text-white text-4xl font-black">L<?php echo number_format($ventas_semana, 2); ?></p>
        <p class="text-gray-500 dark:text-[#92a4c9] text-sm mt-2">Ingresos de esta semana</p>
    </div>

    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center gap-3 mb-2">
            <span class="material-symbols-outlined text-primary">calendar_month</span>
            <h3 class="text-gray-900 dark:text-white text-lg font-bold">Ventas del Mes</h3>
        </div>
        <p class="text-gray-900 dark:text-white text-4xl font-black">L<?php echo number_format($ventas_mes, 2); ?></p>
        <p class="text-gray-500 dark:text-[#92a4c9] text-sm mt-2">Ingresos del mes actual</p>
    </div>
</div>

<!-- Estadísticas adicionales -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center gap-3 mb-2">
            <span class="material-symbols-outlined text-primary">receipt</span>
            <h3 class="text-gray-900 dark:text-white text-lg font-bold">Ticket Promedio</h3>
        </div>
        <p class="text-gray-900 dark:text-white text-4xl font-black">L<?php echo number_format($ticket_promedio, 2); ?></p>
        <p class="text-gray-500 dark:text-[#92a4c9] text-sm mt-2">Promedio por transacción</p>
    </div>

    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center gap-3 mb-2">
            <span class="material-symbols-outlined text-primary">badge</span>
            <h3 class="text-gray-900 dark:text-white text-lg font-bold">Usuarios del Sistema</h3>
        </div>
        <p class="text-gray-900 dark:text-white text-4xl font-black"><?php echo number_format($total_usuarios); ?></p>
        <p class="text-gray-500 dark:text-[#92a4c9] text-sm mt-2">Total de usuarios registrados</p>
    </div>
</div>

<!-- Gráficos -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Ventas últimos 7 días -->
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <h3 class="text-gray-900 dark:text-white text-lg font-bold mb-4">Ventas Últimos 7 Días</h3>
        <canvas id="ventasDiasChart"></canvas>
    </div>

    <!-- Ventas últimos 6 meses -->
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <h3 class="text-gray-900 dark:text-white text-lg font-bold mb-4">Ventas Últimos 6 Meses</h3>
        <canvas id="ventasMesesChart"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Top productos -->
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <h3 class="text-gray-900 dark:text-white text-lg font-bold mb-4">Top 5 Productos Más Vendidos</h3>
        <canvas id="topProductosChart"></canvas>
    </div>

    <!-- Métodos de pago -->
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <h3 class="text-gray-900 dark:text-white text-lg font-bold mb-4">Métodos de Pago</h3>
        <canvas id="metodosPagoChart"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Top clientes -->
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <h3 class="text-gray-900 dark:text-white text-lg font-bold mb-4">Top 5 Clientes que Más Compran</h3>
        <canvas id="topClientesChart"></canvas>
    </div>

    <!-- Ventas por vendedor -->
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <h3 class="text-gray-900 dark:text-white text-lg font-bold mb-4">Ventas por Vendedor</h3>
        <canvas id="ventasVendedorChart"></canvas>
    </div>
</div>

</div>
</main>
</div>

<script>
// Configuración global de Chart.js
Chart.defaults.color = '#92a4c9';
Chart.defaults.borderColor = '#324467';

// Ventas últimos 7 días
const ctx1 = document.getElementById('ventasDiasChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels_7dias); ?>,
        datasets: [{
            label: 'Ventas (L)',
            data: <?php echo json_encode($ventas_7dias); ?>,
            borderColor: '#137fec',
            backgroundColor: 'rgba(19, 127, 236, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'L' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Ventas últimos 6 meses
const ctx2 = document.getElementById('ventasMesesChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels_6meses); ?>,
        datasets: [{
            label: 'Ventas (L)',
            data: <?php echo json_encode($ventas_6meses); ?>,
            backgroundColor: '#137fec',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'L' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Top productos
const ctx3 = document.getElementById('topProductosChart').getContext('2d');
new Chart(ctx3, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($productos_nombres); ?>,
        datasets: [{
            data: <?php echo json_encode($productos_cantidades); ?>,
            backgroundColor: [
                '#137fec',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Métodos de pago
const ctx4 = document.getElementById('metodosPagoChart').getContext('2d');
new Chart(ctx4, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($metodos_nombres); ?>,
        datasets: [{
            data: <?php echo json_encode($metodos_valores); ?>,
            backgroundColor: [
                '#137fec',
                '#10b981',
                '#f59e0b',
                '#ef4444'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Top clientes
const ctx5 = document.getElementById('topClientesChart').getContext('2d');
new Chart(ctx5, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($clientes_nombres); ?>,
        datasets: [{
            label: 'Total Comprado (L)',
            data: <?php echo json_encode($clientes_totales); ?>,
            backgroundColor: '#10b981',
        }]
    },
    options: {
        responsive: true,
        indexAxis: 'y',
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'L' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Ventas por vendedor
const ctx6 = document.getElementById('ventasVendedorChart').getContext('2d');
new Chart(ctx6, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($vendedores_nombres); ?>,
        datasets: [{
            data: <?php echo json_encode($vendedores_ventas); ?>,
            backgroundColor: [
                '#137fec',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

</body></html>
<?php
$conexion->close();
?>
