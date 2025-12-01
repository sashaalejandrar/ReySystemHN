<?php
// notificaciones_component.php
// Componente moderno y elegante de notificaciones para ReySystem
?>

<!-- SISTEMA DE NOTIFICACIONES REYSYSTEM -->
<div class="relative" x-data="notificationsSystem()" x-init="init()">
    <!-- Botón de Notificaciones -->
    <button 
        @click="togglePanel()" 
        class="relative p-2.5 rounded-xl hover:bg-gradient-to-br hover:from-blue-500/10 hover:to-purple-500/10 transition-all duration-300 group"
        :class="{ 'bg-gradient-to-br from-blue-500/20 to-purple-500/20': open }"
    >
        <!-- Icono de campana con animación -->
        <div class="relative">
            <span class="material-symbols-outlined text-2xl text-gray-600 dark:text-gray-300 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors duration-300"
                  :class="{ 'animate-wiggle': hasUnread }">
                notifications
            </span>
            
            <!-- Badge de contador -->
            <template x-if="unreadCount > 0">
                <div class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1.5 flex items-center justify-center">
                    <!-- Efecto de pulso de fondo -->
                    <span class="absolute inset-0 bg-gradient-to-r from-red-500 to-pink-500 rounded-full animate-ping opacity-75"></span>
                    <!-- Badge principal -->
                    <span class="relative bg-gradient-to-r from-red-500 to-pink-600 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 shadow-lg border border-white/20"
                          x-text="unreadCount > 99 ? '99+' : unreadCount">
                    </span>
                </div>
            </template>
        </div>
    </button>

    <!-- Panel de Notificaciones -->
    <div 
        x-show="open" 
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-3 w-96 max-w-[calc(100vw-2rem)] z-50"
        style="display: none;"
    >
        <!-- Contenedor principal con glassmorphism -->
        <div class="bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden">
            <!-- Header del panel -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-xl">notifications_active</span>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-lg">Notificaciones</h3>
                            <p class="text-white/80 text-xs" x-text="unreadCount > 0 ? unreadCount + ' sin leer' : 'Todo al día'"></p>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="flex items-center gap-2">
                        <template x-if="unreadCount > 0">
                            <button 
                                @click="markAllAsRead()"
                                class="px-3 py-1.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg text-white text-xs font-medium transition-all duration-200 flex items-center gap-1"
                            >
                                <span class="material-symbols-outlined text-sm">done_all</span>
                                <span>Marcar todas</span>
                            </button>
                        </template>
                        <button 
                            @click="open = false"
                            class="w-8 h-8 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg flex items-center justify-center transition-all duration-200"
                        >
                            <span class="material-symbols-outlined text-white text-lg">close</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filtros/Tabs -->
            <div class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700 px-4 py-2">
                <div class="flex gap-2">
                    <button 
                        @click="filter = 'all'"
                        :class="filter === 'all' ? 'bg-white dark:bg-gray-700 shadow-sm' : 'hover:bg-white/50 dark:hover:bg-gray-700/50'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200"
                    >
                        Todas
                    </button>
                    <button 
                        @click="filter = 'stock'"
                        :class="filter === 'stock' ? 'bg-white dark:bg-gray-700 shadow-sm' : 'hover:bg-white/50 dark:hover:bg-gray-700/50'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 flex items-center gap-1"
                    >
                        <span class="material-symbols-outlined text-sm">inventory</span>
                        Stock
                    </button>
                    <button 
                        @click="filter = 'system'"
                        :class="filter === 'system' ? 'bg-white dark:bg-gray-700 shadow-sm' : 'hover:bg-white/50 dark:hover:bg-gray-700/50'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 flex items-center gap-1"
                    >
                        <span class="material-symbols-outlined text-sm">settings</span>
                        Sistema
                    </button>
                </div>
            </div>

            <!-- Lista de notificaciones -->
            <div class="max-h-[28rem] overflow-y-auto custom-scrollbar">
                <template x-if="filteredNotifications.length > 0">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="(notification, index) in filteredNotifications" :key="notification.id">
                            <div 
                                class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-all duration-200 cursor-pointer group relative overflow-hidden"
                                :class="{ 'bg-blue-50/50 dark:bg-blue-900/10': !notification.leida }"
                                @click="markAsRead(notification.id)"
                            >
                                <!-- Indicador de no leída -->
                                <template x-if="!notification.leida">
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-500 to-purple-500"></div>
                                </template>

                                <div class="flex gap-3">
                                    <!-- Icono -->
                                    <div 
                                        class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center"
                                        :class="getNotificationStyle(notification.tipo).bgClass"
                                    >
                                        <span 
                                            class="material-symbols-outlined text-lg"
                                            :class="getNotificationStyle(notification.tipo).iconColor"
                                            x-text="getNotificationStyle(notification.tipo).icon"
                                        ></span>
                                    </div>

                                    <!-- Contenido -->
                                    <div class="flex-1 min-w-0">
                                        <!-- Tipo y tiempo -->
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <span 
                                                class="text-xs font-semibold uppercase tracking-wide"
                                                :class="getNotificationStyle(notification.tipo).textColor"
                                                x-text="getNotificationLabel(notification.tipo)"
                                            ></span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500" x-text="getTimeAgo(notification.fecha_creacion)"></span>
                                        </div>

                                        <!-- Mensaje -->
                                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-2" x-text="notification.mensaje"></p>

                                        <!-- Acciones -->
                                        <div class="flex items-center gap-2">
                                            <template x-if="notification.Codigo_Producto">
                                                <a 
                                                    :href="'inventario.php?buscar=' + encodeURIComponent(notification.Codigo_Producto)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-lg text-xs font-medium transition-all duration-200"
                                                    @click.stop
                                                >
                                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                                    Ver producto
                                                </a>
                                            </template>
                                            
                                            <template x-if="!notification.leida">
                                                <button 
                                                    @click.stop="markAsRead(notification.id)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-medium transition-all duration-200"
                                                >
                                                    <span class="material-symbols-outlined text-sm">check</span>
                                                    Marcar leída
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Estado vacío -->
                <template x-if="filteredNotifications.length === 0">
                    <div class="p-12 text-center">
                        <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-2xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-4xl text-gray-400 dark:text-gray-500">notifications_off</span>
                        </div>
                        <h4 class="text-gray-700 dark:text-gray-300 font-semibold mb-1">Sin notificaciones</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No tienes notificaciones en este momento</p>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <template x-if="filteredNotifications.length > 0">
                <div class="bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 p-3">
                    <button 
                        @click="loadMore()"
                        class="w-full py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors duration-200"
                    >
                        Ver todas las notificaciones
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>

<!-- Estilos adicionales -->
<style>
@keyframes wiggle {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-10deg); }
    75% { transform: rotate(10deg); }
}

.animate-wiggle {
    animation: wiggle 0.5s ease-in-out infinite;
}

/* Scrollbar personalizado */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.3);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(156, 163, 175, 0.5);
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(75, 85, 99, 0.5);
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(75, 85, 99, 0.7);
}
</style>

<!-- Script de Alpine.js para el componente -->
<script>
function notificationsSystem() {
    return {
        open: false,
        notifications: <?php echo json_encode($notificaciones_pendientes ?? []); ?>,
        unreadCount: <?php echo $total_notificaciones ?? 0; ?>,
        filter: 'all',
        
        get hasUnread() {
            return this.unreadCount > 0;
        },
        
        init() {
            // Actualizar notificaciones cada 30 segundos
            setInterval(() => {
                this.refreshNotifications();
            }, 30000);
        },
        
        togglePanel() {
            this.open = !this.open;
        },
        
        get filteredNotifications() {
            if (this.filter === 'all') {
                return this.notifications;
            }
            
            if (this.filter === 'stock') {
                return this.notifications.filter(n => 
                    ['stock_bajo', 'sin_stock', 'por_vencer'].includes(n.tipo)
                );
            }
            
            if (this.filter === 'system') {
                return this.notifications.filter(n => 
                    !['stock_bajo', 'sin_stock', 'por_vencer'].includes(n.tipo)
                );
            }
            
            return this.notifications;
        },
        
        getNotificationStyle(tipo) {
            const styles = {
                'stock_bajo': {
                    icon: 'inventory',
                    iconColor: 'text-yellow-600 dark:text-yellow-400',
                    bgClass: 'bg-yellow-100 dark:bg-yellow-900/30',
                    textColor: 'text-yellow-700 dark:text-yellow-400'
                },
                'sin_stock': {
                    icon: 'remove_shopping_cart',
                    iconColor: 'text-red-600 dark:text-red-400',
                    bgClass: 'bg-red-100 dark:bg-red-900/30',
                    textColor: 'text-red-700 dark:text-red-400'
                },
                'por_vencer': {
                    icon: 'event_busy',
                    iconColor: 'text-orange-600 dark:text-orange-400',
                    bgClass: 'bg-orange-100 dark:bg-orange-900/30',
                    textColor: 'text-orange-700 dark:text-orange-400'
                },
                'default': {
                    icon: 'info',
                    iconColor: 'text-blue-600 dark:text-blue-400',
                    bgClass: 'bg-blue-100 dark:bg-blue-900/30',
                    textColor: 'text-blue-700 dark:text-blue-400'
                }
            };
            
            return styles[tipo] || styles.default;
        },
        
        getNotificationLabel(tipo) {
            const labels = {
                'stock_bajo': 'Stock Bajo',
                'sin_stock': 'Sin Stock',
                'por_vencer': 'Por Vencer',
                'default': 'Notificación'
            };
            
            return labels[tipo] || labels.default;
        },
        
        getTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            if (seconds < 60) return 'Ahora';
            if (seconds < 3600) return Math.floor(seconds / 60) + 'm';
            if (seconds < 86400) return Math.floor(seconds / 3600) + 'h';
            if (seconds < 604800) return Math.floor(seconds / 86400) + 'd';
            return Math.floor(seconds / 604800) + 'sem';
        },
        
        async markAsRead(notificationId) {
            try {
                const response = await fetch('marcar_notificacion_leida.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: notificationId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Actualizar localmente
                    const notification = this.notifications.find(n => n.id == notificationId);
                    if (notification) {
                        notification.leida = 1;
                        this.unreadCount = Math.max(0, this.unreadCount - 1);
                    }
                }
            } catch (error) {
                console.error('Error al marcar notificación:', error);
            }
        },
        
        async markAllAsRead() {
            try {
                const response = await fetch('marcar_notificaciones_leidas.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Actualizar localmente
                    this.notifications.forEach(n => n.leida = 1);
                    this.unreadCount = 0;
                }
            } catch (error) {
                console.error('Error al marcar todas:', error);
            }
        },
        
        async refreshNotifications() {
            try {
                const response = await fetch('obtener_notificaciones.php');
                const data = await response.json();
                
                if (data.success) {
                    this.notifications = data.notificaciones;
                    this.unreadCount = data.total_no_leidas;
                }
            } catch (error) {
                console.error('Error al actualizar notificaciones:', error);
            }
        },
        
        loadMore() {
            // Implementar navegación a página de todas las notificaciones
            window.location.href = 'notificaciones.php';
        }
    }
}
</script>
