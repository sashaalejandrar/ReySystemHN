<?php
// menu_lateral.php
// Menú lateral reorganizado con categorías desplegables

// Detectar la página actual
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Función helper para determinar si un enlace está activo
function esActivo($pagina) {
    global $pagina_actual;
    return $pagina_actual === $pagina;
}

// Función para determinar si una categoría contiene la página activa
function categoriaActiva($paginas) {
    foreach ($paginas as $pagina) {
        if (esActivo($pagina)) {
            return true;
        }
    }
    return false;
}
?>

<!-- SideNavBar Navideño Mejorado -->
<aside class="w-64 flex-shrink-0 bg-gradient-to-b from-[#0f172a] via-[#1e293b] to-[#0f172a] p-4 flex flex-col justify-between overflow-y-auto border-r border-red-900/30 shadow-2xl" style="backdrop-filter: blur(10px);">
    <div class="flex flex-col gap-6">
        <!-- Header con Corona Navideña -->
        <div class="flex items-center gap-3 p-3 mb-4 relative">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-600/20 to-green-600/20 flex items-center justify-center shadow-lg border border-yellow-500/30">
                <span class="material-symbols-outlined text-yellow-500 text-2xl" style="filter: drop-shadow(0 0 8px rgba(234, 179, 8, 0.6));">payments</span>
            </div>
            <h1 class="text-xl font-black text-white tracking-tight relative">
                <span class="relative inline-block">
                    <span class="relative z-10">R</span>
                    <!-- Corona SVG inclinada -->
                    <svg class="absolute -top-3 -left-1" width="28" height="28" viewBox="0 0 24 24" style="transform: rotate(-15deg); filter: drop-shadow(0 2px 4px rgba(234, 179, 8, 0.4));">
                        <path d="M12 2L14.5 8.5L21 9L16 14L18 21L12 17.5L6 21L8 14L3 9L9.5 8.5L12 2Z" fill="#fbbf24" stroke="#f59e0b" stroke-width="0.5"/>
                        <circle cx="12" cy="9" r="1.5" fill="#dc2626"/>
                        <circle cx="8" cy="10" r="1" fill="#16a34a"/>
                        <circle cx="16" cy="10" r="1" fill="#16a34a"/>
                    </svg>
                </span>ey System
            </h1>
        </div>
        
        <!-- User Info Navideño -->
        <div class="flex items-center gap-3 p-3 mb-4 rounded-xl shadow-lg border border-red-900/30" style="background: linear-gradient(135deg, rgba(220, 38, 38, 0.1) 0%, rgba(22, 163, 74, 0.1) 100%); backdrop-filter: blur(10px);">
            <div class="relative">
                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-12 ring-2 ring-yellow-500/50" style='background-image: url("<?php echo $Perfil;?>"); box-shadow: 0 0 15px rgba(234, 179, 8, 0.3);'></div>
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-yellow-400" style="box-shadow: 0 0 8px rgba(34, 197, 94, 0.6);"></div>
            </div>
            <div class="flex flex-col flex-1">
                <p class="text-sm font-bold text-white truncate" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);"><?php echo $Nombre_Completo; ?></p>
                <p class="text-xs text-yellow-200 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-400" style="box-shadow: 0 0 6px rgba(234, 179, 8, 0.6);"></span>
                    <?php echo ucfirst($Rol); ?>
                </p>
            </div>
        </div>

        <!-- Menu Categories -->
        <nav class="flex flex-col gap-2">
            
            <!-- Dashboard Navideño -->
            <a href="index.php" class="<?php echo esActivo('index.php') ? 'shadow-lg border-l-4 border-yellow-500' : 'border-l-4 border-transparent hover:border-red-500/50'; ?> flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all group" style="<?php echo esActivo('index.php') ? 'background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(22, 163, 74, 0.2) 100%); backdrop-filter: blur(10px);' : ''; ?> backdrop-filter: blur(5px);" onmouseover="this.style.background='linear-gradient(135deg, rgba(220, 38, 38, 0.15) 0%, rgba(22, 163, 74, 0.15) 100%)'; this.style.transform='translateX(4px)';" onmouseout="this.style.background='<?php echo esActivo('index.php') ? 'linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(22, 163, 74, 0.2) 100%)' : ''; ?>'; this.style.transform='translateX(0)';">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-all" style="<?php echo esActivo('index.php') ? 'background: linear-gradient(135deg, #dc2626 0%, #16a34a 100%); box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);' : 'background: rgba(255, 255, 255, 0.1);'; ?>">
                    <span class="material-symbols-outlined text-white text-xl" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">dashboard</span>
                </div>
                <p class="text-sm font-semibold text-white" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Dashboard</p>
            </a>

            <!-- Caja al Día -->
            <?php if ($rol_usuario === 'cajero/gerente' || $rol_usuario === 'admin'): ?>
            <div class="menu-category">
                <button onclick="toggleCategory('caja')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all group border-l-4 border-transparent hover:border-red-500/50" style="backdrop-filter: blur(5px);" onmouseover="this.style.background='linear-gradient(135deg, rgba(220, 38, 38, 0.15) 0%, rgba(22, 163, 74, 0.15) 100%)'; this.style.transform='translateX(4px)';" onmouseout="this.style.background=''; this.style.transform='translateX(0)';">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-all" style="background: rgba(255, 255, 255, 0.1);">
                            <span class="material-symbols-outlined text-white text-xl" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">point_of_sale</span>
                        </div>
                        <p class="text-sm font-semibold text-white" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Caja al Día</p>
                    </div>
                    <span id="caja-icon" class="material-symbols-outlined text-white text-lg transition-transform" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">expand_more</span>
                </button>
                <div id="caja-menu" class="ml-6 mt-1 flex-col gap-1 <?php echo categoriaActiva(['apertura_caja.php', 'arqueo_caja.php', 'cierre_caja.php', 'reportes_caja.php', 'compra_desde_ventas.php', 'ver_egresos.php']) ? '' : 'hidden'; ?>">
                    <a href="apertura_caja.php" class="<?php echo esActivo('apertura_caja.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">lock_open</span>
                        Apertura de Caja
                    </a>
                    <a href="arqueo_caja.php" class="<?php echo esActivo('arqueo_caja.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">calculate</span>
                        Arqueo de Caja
                    </a>
                    <a href="cierre_caja.php" class="<?php echo esActivo('cierre_caja.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">lock</span>
                        Cierre de Caja
                    </a>
                    <a href="reportes_caja.php" class="<?php echo esActivo('reportes_caja.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">receipt_long</span>
                        Reportes de Caja
                    </a>
                    <a href="compra_desde_ventas.php" class="<?php echo esActivo('compra_desde_ventas.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">shopping_bag</span>
                        Compra desde Ventas
                    </a>
                    <?php if ($rol_usuario === 'admin'): ?>
                    <a href="ver_egresos.php" class="<?php echo esActivo('ver_egresos.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">receipt_long</span>
                        Ver Egresos
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Inventario -->
            <div class="menu-category">
                <button onclick="toggleCategory('inventario')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all group border-l-4 border-transparent hover:border-green-500/50" style="backdrop-filter: blur(5px);" onmouseover="this.style.background='linear-gradient(135deg, rgba(220, 38, 38, 0.15) 0%, rgba(22, 163, 74, 0.15) 100%)'; this.style.transform='translateX(4px)';" onmouseout="this.style.background=''; this.style.transform='translateX(0)';">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-all" style="background: rgba(255, 255, 255, 0.1);">
                            <span class="material-symbols-outlined text-white text-xl" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">inventory_2</span>
                        </div>
                        <p class="text-sm font-semibold text-white" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Inventario</p>
                    </div>
                    <span id="inventario-icon" class="material-symbols-outlined text-white text-lg transition-transform" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">expand_more</span>
                </button>
                <div id="inventario-menu" class="ml-6 mt-1 flex-col gap-1 <?php echo categoriaActiva(['inventario.php', 'creacion_de_producto.php', 'historial_inventario.php', 'productos_pendientes.php', 'creacion_productos_lote.php', 'ingreso_actualizacion_producto_lote.php', 'escanear_factura.php', 'escaneo_productos.php', 'gestion_categorias.php', 'gestion_marcas.php']) ? '' : 'hidden'; ?>">
                    <a href="inventario.php" class="<?php echo esActivo('inventario.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">inventory</span>
                        Gestión de Inventario
                    </a>
                    <a href="creacion_de_producto.php" class="<?php echo esActivo('creacion_de_producto.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">add_box</span>
                        Creación de Producto
                    </a>
                    <a href="historial_inventario.php" class="<?php echo esActivo('historial_inventario.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">history</span>
                        Historial de Inventario
                    </a>
                    
                    <?php if ($rol_usuario === 'admin'): ?>
                    <a href="productos_pendientes.php" class="<?php echo esActivo('productos_pendientes.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">pending_actions</span>
                        Productos Pendientes
                    </a>
                    
                    <!-- Separador visual -->
                    <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                    
                    <a href="gestion_categorias.php" class="<?php echo esActivo('gestion_categorias.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">category</span>
                        Gestión de Categorías
                    </a>
                    <a href="gestion_marcas.php" class="<?php echo esActivo('gestion_marcas.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">label</span>
                        Gestión de Marcas
                    </a>
                    
                    <!-- Separador visual -->
                    <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                    
                    <a href="creacion_productos_lote.php" class="<?php echo esActivo('creacion_productos_lote.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">library_add</span>
                        Creación en Lote
                    </a>
                    <a href="ingreso_actualizacion_producto_lote.php" class="<?php echo esActivo('ingreso_actualizacion_producto_lote.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">update</span>
                        Actualización en Lote
                    </a>
                    <a href="escanear_factura.php" class="<?php echo esActivo('escanear_factura.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">photo_camera</span>
                        Escanear Factura con IA
                    </a>
                    <a href="escaneo_productos.php" class="<?php echo esActivo('escaneo_productos.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">qr_code_scanner</span>
                        📱 Escaneo de Productos
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ventas -->
            <div class="menu-category">
                <button onclick="toggleCategory('ventas')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-gray-600 dark:text-white">shopping_cart</span>
                        <p class="text-sm font-medium text-gray-600 dark:text-white">Ventas</p>
                    </div>
                    <span id="ventas-icon" class="material-symbols-outlined text-gray-600 dark:text-white text-sm transition-transform">expand_more</span>
                </button>
                <div id="ventas-menu" class="ml-6 mt-1 flex-col gap-1 <?php echo categoriaActiva(['nueva_venta.php', 'lista_deudas.php', 'ventas.php']) ? '' : 'hidden'; ?>">
                    <a href="nueva_venta.php" class="<?php echo esActivo('nueva_venta.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">add_shopping_cart</span>
                        Nueva Venta
                    </a>
                    <a href="lista_deudas.php" class="<?php echo esActivo('lista_deudas.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">credit_card</span>
                        Ventas a Crédito
                    </a>
                    <a href="reporte_ventas.php" class="<?php echo esActivo('reporte_ventas.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">list_alt</span>
                        Historial de Ventas
                    </a>
                </div>
            </div>

            <!-- Clientes -->
            <?php if ($rol_usuario === 'admin'): ?>
            <div class="menu-category">
                <button onclick="toggleCategory('clientes')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-gray-600 dark:text-white">group</span>
                        <p class="text-sm font-medium text-gray-600 dark:text-white">Clientes</p>
                    </div>
                    <span id="clientes-icon" class="material-symbols-outlined text-gray-600 dark:text-white text-sm transition-transform">expand_more</span>
                </button>
                <div id="clientes-menu" class="ml-6 mt-1 flex-col gap-1 <?php echo categoriaActiva(['clientes.php']) ? '' : 'hidden'; ?>">
                    <a href="clientes.php" class="<?php echo esActivo('clientes.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">manage_accounts</span>
                        Gestión de Clientes
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Fidelización -->
            <?php if ($rol_usuario === 'cajero/gerente' || $rol_usuario === 'admin'): ?>
            <div class="menu-category">
                <button onclick="toggleCategory('fidelizacion')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-gray-600 dark:text-white">stars</span>
                        <p class="text-sm font-medium text-gray-600 dark:text-white">Fidelización</p>
                    </div>
                    <span id="fidelizacion-icon" class="material-symbols-outlined text-gray-600 dark:text-white text-sm transition-transform">expand_more</span>
                </button>
                <div id="fidelizacion-menu" class="ml-6 mt-1 flex-col gap-1 <?php echo categoriaActiva(['puntos_fidelidad.php', 'recompensas.php', 'generar_qr.php', 'gestion_membresias.php']) ? '' : 'hidden'; ?>">
                    <a href="puntos_fidelidad.php" class="<?php echo esActivo('puntos_fidelidad.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">loyalty</span>
                        Puntos de Fidelidad
                    </a>
                    <a href="recompensas.php" class="<?php echo esActivo('recompensas.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">redeem</span>
                        Recompensas
                    </a>
                    <a href="generar_qr.php" class="<?php echo esActivo('generar_qr.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">qr_code</span>
                        Registro por QR
                    </a>
                    <?php if ($rol_usuario === 'admin'): ?>
                    <a href="gestion_membresias.php" class="<?php echo esActivo('gestion_membresias.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">card_membership</span>
                        Gestión Membresías
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Gamificación -->
            <div class="menu-category">
                <button onclick="toggleCategory('gamificacion')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-gray-600 dark:text-white">emoji_events</span>
                        <p class="text-sm font-medium text-gray-600 dark:text-white">Gamificación</p>
                    </div>
                    <span id="gamificacion-icon" class="material-symbols-outlined text-gray-600 dark:text-white text-sm transition-transform">expand_more</span>
                </button>
                <div id="gamificacion-menu" class="ml-6 mt-1 flex-col gap-1 <?php echo categoriaActiva(['logros.php', 'mis_logros.php']) ? '' : 'hidden'; ?>">
                    <?php if ($rol_usuario === 'admin' || $rol_usuario === 'cajero/gerente'): ?>
                    <a href="logros.php" class="<?php echo esActivo('logros.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">workspace_premium</span>
                        Logros
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bienestar Mental -->
            <a href="bienestar_mental.php" class="<?php echo esActivo('bienestar_mental.php') ? 'bg-primary/20 dark:bg-[#232f48]' : 'hover:bg-gray-200 dark:hover:bg-white/10'; ?> flex items-center gap-3 px-3 py-2 rounded-lg transition-colors">
                <span class="material-symbols-outlined <?php echo esActivo('bienestar_mental.php') ? 'text-primary dark:text-white' : 'text-gray-600 dark:text-white'; ?>">self_improvement</span>
                <p class="text-sm font-medium <?php echo esActivo('bienestar_mental.php') ? 'text-primary dark:text-white' : 'text-gray-600 dark:text-white'; ?>">Bienestar Mental</p>
            </a>

            <!-- Reportes -->
            <?php if ($rol_usuario === 'admin'): ?>
            <div class="menu-category">
                <button onclick="toggleCategory('reportes')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-gray-600 dark:text-white">analytics</span>
                        <p class="text-sm font-medium text-gray-600 dark:text-white">Reportes</p>
                    </div>
                    <span id="reportes-icon" class="material-symbols-outlined text-gray-600 dark:text-white text-sm transition-transform">expand_more</span>
                </button>
                <div id="reportes-menu" class="ml-6 mt-1 flex-col gap-1 <?php echo categoriaActiva(['estadisticas.php']) ? '' : 'hidden'; ?>">
                    <a href="estadisticas.php" class="<?php echo esActivo('estadisticas.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">bar_chart</span>
                        Estadísticas
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pedidos -->
            <?php if ($rol_usuario === 'admin' || $rol_usuario === 'cajero/gerente'): ?>
            <div class="menu-category">
                <button onclick="toggleCategory('pedidos')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-gray-600 dark:text-white">shopping_bag</span>
                        <p class="text-sm font-medium text-gray-600 dark:text-white">Pedidos</p>
                    </div>
                    <span id="pedidos-icon" class="material-symbols-outlined text-gray-600 dark:text-white text-sm transition-transform">expand_more</span>
                </button>
                <div id="pedidos-menu" class="ml-6 mt-1 flex-col gap-1 <?php echo categoriaActiva(['pedidos.php', 'pedidos_simples.php', 'pedidos_mayoreo.php', 'ver_pedidos.php', 'reportes_pedidos.php']) ? '' : 'hidden'; ?>">
                    <a href="pedidos.php" class="<?php echo esActivo('pedidos.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">add_shopping_cart</span>
                        Nuevo Pedido
                    </a>
                    <a href="pedidos_simples.php" class="<?php echo esActivo('pedidos_simples.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">note_add</span>
                        Pedidos Productos No Existentes
                    </a>
                    <a href="pedidos_mayoreo.php" class="<?php echo esActivo('pedidos_mayoreo.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">inventory_2</span>
                        Pedidos por Mayoreo
                    </a>
                    <a href="ver_pedidos.php" class="<?php echo esActivo('ver_pedidos.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">list_alt</span>
                        Ver Pedidos
                    </a>
                    <a href="reportes_pedidos.php" class="<?php echo esActivo('reportes_pedidos.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">assessment</span>
                        Reportes
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Foro -->
            <a href="foro.php" class="<?php echo esActivo('foro.php') ? 'bg-primary/20 dark:bg-[#232f48]' : 'hover:bg-gray-200 dark:hover:bg-white/10'; ?> flex items-center gap-3 px-3 py-2 rounded-lg transition-colors">
                <span class="material-symbols-outlined <?php echo esActivo('foro.php') ? 'text-primary dark:text-white' : 'text-gray-600 dark:text-white'; ?>">forum</span>
                <p class="text-sm font-medium <?php echo esActivo('foro.php') ? 'text-primary dark:text-white' : 'text-gray-600 dark:text-white'; ?>">Foro</p>
            </a>

            <!-- Chat -->
            <a href="chat.php" class="<?php echo esActivo('chat.php') ? 'bg-primary/20 dark:bg-[#232f48]' : 'hover:bg-gray-200 dark:hover:bg-white/10'; ?> flex items-center gap-3 px-3 py-2 rounded-lg transition-colors">
                <span class="material-symbols-outlined <?php echo esActivo('chat.php') ? 'text-primary dark:text-white' : 'text-gray-600 dark:text-white'; ?>">chat</span>
                <p class="text-sm font-medium <?php echo esActivo('chat.php') ? 'text-primary dark:text-white' : 'text-gray-600 dark:text-white'; ?>">Chat</p>
            </a>

            <!-- Contabilidad -->
            <?php if ($rol_usuario === 'admin' || $rol_usuario === 'contador'): ?>
            <div class="menu-category">
                <button onclick="toggleCategory('contabilidad')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-gray-600 dark:text-white">account_balance</span>
                        <p class="text-sm font-medium text-gray-600 dark:text-white">Contabilidad</p>
                    </div>
                    <span id="contabilidad-icon" class="material-symbols-outlined text-gray-600 dark:text-white text-sm transition-transform">expand_more</span>
                </button>
                <div id="contabilidad-menu" class="ml-6 mt-1 flex-col gap-1 <?php echo categoriaActiva(['contabilidad.php', 'reporte_ventas_mensuales.php', 'libro_compras.php', 'libro_ventas.php', 'conciliacion_bancaria.php', 'estado_resultados.php', 'balance_general.php', 'declaracion_isv.php', 'retenciones.php']) ? '' : 'hidden'; ?>">
                    <a href="contabilidad.php" class="<?php echo esActivo('contabilidad.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">receipt_long</span>
                        Panel Contable
                    </a>
                    <a href="reporte_ventas_mensuales.php" class="<?php echo esActivo('reporte_ventas_mensuales.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">trending_up</span>
                        Ventas Mensuales
                    </a>
                    <a href="libro_ventas.php" class="<?php echo esActivo('libro_ventas.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">book</span>
                        Libro de Ventas
                    </a>
                    <a href="libro_compras.php" class="<?php echo esActivo('libro_compras.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">shopping_cart</span>
                        Libro de Compras
                    </a>
                    <a href="estado_resultados.php" class="<?php echo esActivo('estado_resultados.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">assessment</span>
                        Estado de Resultados
                    </a>
                    <a href="balance_general.php" class="<?php echo esActivo('balance_general.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">balance</span>
                        Balance General
                    </a>
                    <a href="declaracion_isv.php" class="<?php echo esActivo('declaracion_isv.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">receipt</span>
                        Declaración ISV
                    </a>
                    <a href="conciliacion_bancaria.php" class="<?php echo esActivo('conciliacion_bancaria.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">account_balance_wallet</span>
                        Conciliación Bancaria
                    </a>
                    <a href="retenciones.php" class="<?php echo esActivo('retenciones.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">payments</span>
                        Retenciones
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Comparador de Precios -->
            <?php if ($rol_usuario === 'admin'): ?>
            <div class="menu-category">
                <button onclick="toggleCategory('comparador')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-gray-600 dark:text-white">compare_arrows</span>
                        <p class="text-sm font-medium text-gray-600 dark:text-white">Comparador Precios</p>
                    </div>
                    <span id="comparador-icon" class="material-symbols-outlined text-gray-600 dark:text-white text-sm transition-transform">expand_more</span>
                </button>
                <div id="comparador-menu" class="ml-6 mt-1 flex-col gap-1 <?php echo categoriaActiva(['comparador_nuevo.php', 'historial_comparaciones.php']) ? '' : 'hidden'; ?>">
                    <a href="comparador_nuevo.php" class="<?php echo esActivo('comparador_nuevo.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">play_circle</span>
                        Iniciar Comparación
                    </a>
                    <a href="historial_comparaciones.php" class="<?php echo esActivo('historial_comparaciones.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">history</span>
                        Historial de Precios
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Mi Cuenta -->
            <div class="menu-category">
                <button onclick="toggleCategory('cuenta')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-gray-600 dark:text-white">person</span>
                        <p class="text-sm font-medium text-gray-600 dark:text-white">Mi Cuenta</p>
                    </div>
                    <span id="cuenta-icon" class="material-symbols-outlined text-gray-600 dark:text-white text-sm transition-transform">expand_more</span>
                </button>
                <div id="cuenta-menu" class="ml-6 mt-1 flex-col gap-1 <?php echo categoriaActiva(['perfil.php', 'agenda.php']) ? '' : 'hidden'; ?>">
                    <a href="perfil.php" class="<?php echo esActivo('perfil.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">account_circle</span>
                        Perfil
                    </a>
                    <a href="agenda.php" class="<?php echo esActivo('agenda.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">event</span>
                        Agenda
                    </a>
                </div>
            </div>

            <!-- Administración (Solo Admin) -->
            <?php if ($rol_usuario === 'admin'): ?>
            <div class="menu-category">
                <button onclick="toggleCategory('admin')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-gray-600 dark:text-white">admin_panel_settings</span>
                        <p class="text-sm font-medium text-gray-600 dark:text-white">Administración</p>
                    </div>
                    <span id="admin-icon" class="material-symbols-outlined text-gray-600 dark:text-white text-sm transition-transform">expand_more</span>
                </button>
                <div id="admin-menu" class="ml-6 mt-1 flex-col gap-1 <?php echo categoriaActiva(['usuarios.php', 'lista_proveedores.php', 'auditoria.php', 'configuracion.php', 'gestionar_releases.php', 'backup_sistema.php', 'notificaciones_lista.php', 'contratos.php', 'comparacion_precios.php', 'historial_comparaciones.php', 'mermas.php', 'cotizaciones.php', 'analisis_abc.php']) ? '' : 'hidden'; ?>">
                    <a href="usuarios.php" class="<?php echo esActivo('usuarios.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">group</span>
                        Usuarios
                    </a>
                    <a href="lista_proveedores.php" class="<?php echo esActivo('lista_proveedores.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">local_shipping</span>
                        Proveedores
                    </a>
                    <a href="auditoria.php" class="<?php echo esActivo('auditoria.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">fact_check</span>
                        Auditoría
                    </a>
                    <a href="configuracion.php" class="<?php echo esActivo('configuracion.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">tune</span>
                        Configuración
                    </a>
                    <a href="gestionar_releases.php" class="<?php echo esActivo('gestionar_releases.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">rocket_launch</span>
                        Gestionar Releases
                    </a>
                    <a href="diagnostico_ia.php" class="<?php echo esActivo('diagnostico_ia.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">psychology</span>
                        Diagnóstico IA
                    </a>
                    <a href="documentacion.php" class="<?php echo esActivo('documentacion.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">menu_book</span>
                        Documentación
                    </a>
                    <a href="backup_sistema.php" class="<?php echo esActivo('backup_sistema.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">backup</span>
                        Backup Sistema
                    </a>
                    <a href="notificaciones_lista.php" class="<?php echo esActivo('notificaciones_lista.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">notifications</span>
                        Notificaciones
                    </a>
                    <a href="contratos.php" class="<?php echo esActivo('contratos.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">description</span>
                        Contratos
                    </a>
                    <a href="mermas.php" class="<?php echo esActivo('mermas.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">inventory_2</span>
                        Mermas y Pérdidas
                    </a>
                    <a href="cotizaciones.php" class="<?php echo esActivo('cotizaciones.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">request_quote</span>
                        Cotizaciones
                    </a>
                    <a href="analisis_abc.php" class="<?php echo esActivo('analisis_abc.php') ? 'bg-primary/10 text-primary dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'; ?> flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <span class="material-symbols-outlined text-xs">analytics</span>
                        Análisis ABC
                    </a>
                </div>
            </div>
            <?php endif; ?>

        </nav>
    </div>

    <!-- Logout Button -->
    <div class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="logout.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-colors">
            <span class="material-symbols-outlined">logout</span>
            <p class="text-sm font-medium">Cerrar Sesión</p>
        </a>
    </div>
</aside>

<script>
function toggleCategory(categoryId) {
    const menu = document.getElementById(categoryId + '-menu');
    const icon = document.getElementById(categoryId + '-icon');
    
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        menu.classList.add('flex');
        icon.style.transform = 'rotate(180deg)';
    } else {
        menu.classList.add('hidden');
        menu.classList.remove('flex');
        icon.style.transform = 'rotate(0deg)';
    }
}

// Auto-expandir categoría activa al cargar
document.addEventListener('DOMContentLoaded', function() {
    const activeCategories = document.querySelectorAll('.menu-category');
    activeCategories.forEach(category => {
        const menu = category.querySelector('[id$="-menu"]');
        if (menu && !menu.classList.contains('hidden')) {
            const categoryId = menu.id.replace('-menu', '');
            const icon = document.getElementById(categoryId + '-icon');
            if (icon) {
                icon.style.transform = 'rotate(180deg)';
            }
        }
    });
});
</script>

<!-- Christmas Theme CSS -->
<link rel="stylesheet" href="christmas_menu_theme.css">

<!-- Christmas Effects -->
<script src="christmas_snow.js"></script>
<script src="christmas_music.js"></script>
