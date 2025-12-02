<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

// Obtener datos del usuario
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$query_usuario = "SELECT * FROM usuarios WHERE usuario = ?";
$stmt_usuario = $conexion->prepare($query_usuario);
$stmt_usuario->bind_param("s", $_SESSION['usuario']);
$stmt_usuario->execute();
$resultado = $stmt_usuario->get_result();

if ($resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    $Rol = $row['Rol'];
    $Nombre_Completo = $row['Nombre'] . " " . $row['Apellido'];
    $Perfil = $row['Perfil'];
}
$rol_usuario = strtolower($Rol);
$stmt_usuario->close();
$conexion->close();
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Bienestar Mental - ReySystem</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        
        @keyframes breathe {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        .breathing-circle {
            animation: breathe 8s ease-in-out infinite;
        }
        
        .floating {
            animation: float 3s ease-in-out infinite;
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .pattern-card {
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .pattern-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .shimmer {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            background-size: 1000px 100%;
            animation: shimmer 3s infinite;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .badge:hover {
            transform: scale(1.1);
        }
        
        .progress-ring {
            transition: stroke-dashoffset 0.5s ease;
        }
    </style>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#1152d4",
                        "background-light": "#f6f6f8",
                        "background-dark": "#101622",
                    },
                    fontFamily: {
                        "display": ["Manrope", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <?php include "pwa-head.php"; ?>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
    
<div class="relative flex h-auto min-h-screen w-full flex-col">
    <div class="flex flex-1">
        <?php include "menu_lateral.php"; ?>
        
        <main class="flex-1 p-6 lg:p-10">
            <!-- Header -->
            <div class="mb-8 fade-in-up">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-4xl font-bold text-slate-800 dark:text-white mb-2">
                            🌟 Bienestar Mental
                        </h1>
                        <p class="text-slate-600 dark:text-slate-400">
                            Herramientas y actividades para tu salud mental
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="showStats()" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-all hover:scale-105 flex items-center gap-2">
                            <span class="material-symbols-outlined">analytics</span>
                            Estadísticas
                        </button>
                        <button onclick="showAchievements()" class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition-all hover:scale-105 flex items-center gap-2">
                            <span class="material-symbols-outlined">emoji_events</span>
                            Logros
                        </button>
                        <button onclick="exportData()" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-all hover:scale-105 flex items-center gap-2">
                            <span class="material-symbols-outlined">download</span>
                            Exportar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8 fade-in-up" style="animation-delay: 0.1s">
                <div class="stat-card rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400 text-sm">Racha Actual</span>
                        <span class="material-symbols-outlined text-orange-500">local_fire_department</span>
                    </div>
                    <div class="text-3xl font-bold text-white" id="currentStreak">0</div>
                    <div class="text-xs text-slate-400 mt-1">días consecutivos</div>
                </div>

                <div class="stat-card rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400 text-sm">Actividades Hoy</span>
                        <span class="material-symbols-outlined text-blue-500">check_circle</span>
                    </div>
                    <div class="text-3xl font-bold text-white" id="todayActivities">0</div>
                    <div class="text-xs text-slate-400 mt-1">completadas</div>
                </div>

                <div class="stat-card rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400 text-sm">Ánimo Promedio</span>
                        <span class="material-symbols-outlined text-green-500">sentiment_satisfied</span>
                    </div>
                    <div class="text-3xl font-bold text-white" id="avgMood">-</div>
                    <div class="text-xs text-slate-400 mt-1">últimos 7 días</div>
                </div>

                <div class="stat-card rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400 text-sm">Logros</span>
                        <span class="material-symbols-outlined text-purple-500">emoji_events</span>
                    </div>
                    <div class="text-3xl font-bold text-white" id="achievementCount">0</div>
                    <div class="text-xs text-slate-400 mt-1">desbloqueados</div>
                </div>
            </div>

            <!-- Secciones principales -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Asperger Support -->
                <div class="pattern-card bg-gradient-to-br from-purple-500/20 to-purple-600/30 dark:from-purple-500/30 dark:to-purple-600/40 border-2 border-purple-500/40 rounded-2xl p-6 cursor-pointer fade-in-up"
                     onclick="openSection('asperger')" style="animation-delay: 0.2s">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg floating">
                            <span class="material-symbols-outlined text-white text-4xl">extension</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-white mb-1">Asperger</h3>
                            <p class="text-sm text-purple-200">Patrones y enfoque</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-white" id="aspergerProgress">0%</div>
                            <div class="text-xs text-purple-200">progreso</div>
                        </div>
                    </div>
                    <p class="text-white/90 text-sm mb-3">
                        Juegos de patrones, puzzles y herramientas de enfoque
                    </p>
                    <div class="flex gap-2 flex-wrap">
                        <span class="badge bg-purple-500/30 text-purple-200">
                            <span class="material-symbols-outlined text-sm">psychology</span>
                            Patrones
                        </span>
                        <span class="badge bg-purple-500/30 text-purple-200">
                            <span class="material-symbols-outlined text-sm">timer</span>
                            Enfoque
                        </span>
                        <span class="badge bg-purple-500/30 text-purple-200">
                            <span class="material-symbols-outlined text-sm">checklist</span>
                            Rutinas
                        </span>
                    </div>
                </div>

                <!-- TDAH Support -->
                <div class="pattern-card bg-gradient-to-br from-orange-500/20 to-orange-600/30 dark:from-orange-500/30 dark:to-orange-600/40 border-2 border-orange-500/40 rounded-2xl p-6 cursor-pointer fade-in-up"
                     onclick="openSection('tdah')" style="animation-delay: 0.3s">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg floating" style="animation-delay: 0.5s">
                            <span class="material-symbols-outlined text-white text-4xl">bolt</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-white mb-1">TDAH</h3>
                            <p class="text-sm text-orange-200">Enfoque y recompensas</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-white" id="tdahProgress">0%</div>
                            <div class="text-xs text-orange-200">progreso</div>
                        </div>
                    </div>
                    <p class="text-white/90 text-sm mb-3">
                        Pomodoro, tareas rápidas y simulador fidget
                    </p>
                    <div class="flex gap-2 flex-wrap">
                        <span class="badge bg-orange-500/30 text-orange-200">
                            <span class="material-symbols-outlined text-sm">schedule</span>
                            Pomodoro
                        </span>
                        <span class="badge bg-orange-500/30 text-orange-200">
                            <span class="material-symbols-outlined text-sm">flash_on</span>
                            Tareas
                        </span>
                        <span class="badge bg-orange-500/30 text-orange-200">
                            <span class="material-symbols-outlined text-sm">toys</span>
                            Fidget
                        </span>
                    </div>
                </div>

                <!-- Anxiety Relief -->
                <div class="pattern-card bg-gradient-to-br from-blue-500/20 to-blue-600/30 dark:from-blue-500/30 dark:to-blue-600/40 border-2 border-blue-500/40 rounded-2xl p-6 cursor-pointer fade-in-up"
                     onclick="openSection('ansiedad')" style="animation-delay: 0.4s">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg floating" style="animation-delay: 1s">
                            <span class="material-symbols-outlined text-white text-4xl">waves</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-white mb-1">Ansiedad</h3>
                            <p class="text-sm text-blue-200">Calma y respiración</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-white" id="ansiedadProgress">0%</div>
                            <div class="text-xs text-blue-200">progreso</div>
                        </div>
                    </div>
                    <p class="text-white/90 text-sm mb-3">
                        Ejercicios de respiración y actividades calmantes
                    </p>
                    <div class="flex gap-2 flex-wrap">
                        <span class="badge bg-blue-500/30 text-blue-200">
                            <span class="material-symbols-outlined text-sm">air</span>
                            Respiración
                        </span>
                        <span class="badge bg-blue-500/30 text-blue-200">
                            <span class="material-symbols-outlined text-sm">music_note</span>
                            Sonidos
                        </span>
                        <span class="badge bg-blue-500/30 text-blue-200">
                            <span class="material-symbols-outlined text-sm">edit_note</span>
                            Diario
                        </span>
                    </div>
                </div>

                <!-- Depression Support -->
                <div class="pattern-card bg-gradient-to-br from-green-500/20 to-green-600/30 dark:from-green-500/30 dark:to-green-600/40 border-2 border-green-500/40 rounded-2xl p-6 cursor-pointer fade-in-up"
                     onclick="openSection('depresion')" style="animation-delay: 0.5s">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg floating" style="animation-delay: 1.5s">
                            <span class="material-symbols-outlined text-white text-4xl">sunny</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-white mb-1">Depresión</h3>
                            <p class="text-sm text-green-200">Ánimo y motivación</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-white" id="depresionProgress">0%</div>
                            <div class="text-xs text-green-200">progreso</div>
                        </div>
                    </div>
                    <p class="text-white/90 text-sm mb-3">
                        Tracker de ánimo, gratitud y logros
                    </p>
                    <div class="flex gap-2 flex-wrap">
                        <span class="badge bg-green-500/30 text-green-200">
                            <span class="material-symbols-outlined text-sm">mood</span>
                            Ánimo
                        </span>
                        <span class="badge bg-green-500/30 text-green-200">
                            <span class="material-symbols-outlined text-sm">favorite</span>
                            Gratitud
                        </span>
                        <span class="badge bg-green-500/30 text-green-200">
                            <span class="material-symbols-outlined text-sm">star</span>
                            Logros
                        </span>
                    </div>
                </div>
            </div>

            <!-- Modal Container -->
            <div id="modalContainer"></div>

        </main>
    </div>
</div>

<script src="js/bienestar_mental_api.js"></script>
<script src="js/bienestar_mental.js"></script>
<script>
    // Initialize stats on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateDashboardStats();
    });
</script>
<?php include 'modal_sistema.php'; ?>
</body>
</html>
