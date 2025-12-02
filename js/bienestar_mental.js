// Bienestar Mental - JavaScript
// Funciones para minijuegos y actividades terapéuticas

// ============================================
// SISTEMA DE LOGROS Y ESTADÍSTICAS
// ============================================

const ACHIEVEMENTS = {
    first_activity: { name: 'Primer Paso', desc: 'Completar primera actividad', icon: 'star', color: 'yellow' },
    streak_3: { name: 'Constante', desc: '3 días consecutivos', icon: 'local_fire_department', color: 'orange' },
    streak_7: { name: 'Dedicado', desc: '7 días consecutivos', icon: 'local_fire_department', color: 'red' },
    pomodoro_10: { name: 'Enfocado', desc: '10 pomodoros completados', icon: 'schedule', color: 'orange' },
    mood_tracker: { name: 'Autoconsciente', desc: 'Registrar ánimo 7 días', icon: 'mood', color: 'green' },
    gratitude_master: { name: 'Agradecido', desc: '10 entradas de gratitud', icon: 'favorite', color: 'pink' },
    pattern_master: { name: 'Maestro de Patrones', desc: '50 patrones completados', icon: 'extension', color: 'purple' },
    zen_master: { name: 'Zen', desc: '20 sesiones de respiración', icon: 'spa', color: 'blue' }
};

// Ambient sounds URLs (using free ambient sounds from reliable sources)
const AMBIENT_SOUNDS = {
    rain_thunder: 'https://assets.mixkit.co/active_storage/sfx/2390/2390-preview.mp3', // Lluvia con rayos
    rain: 'https://assets.mixkit.co/active_storage/sfx/2393/2393-preview.mp3', // Lluvia suave
    forest: 'https://assets.mixkit.co/active_storage/sfx/2462/2462-preview.mp3',
    none: null
};

const SOUND_LABELS = {
    rain_thunder: '⛈️ Lluvia con Rayos',
    rain: '🌧️ Lluvia',
    forest: '🌲 Bosque',
    none: '🔇 Sin sonido'
};

let currentAmbientSound = null;
let ambientAudio = null;

// Check and unlock achievements
async function checkAchievements() {
    try {
        const unlocked = await getAchievementsDB();
        const stats = await getGlobalStats();

        const toCheck = [
            { id: 'first_activity', condition: stats.totalActivities >= 1 },
            { id: 'streak_3', condition: stats.currentStreak >= 3 },
            { id: 'streak_7', condition: stats.currentStreak >= 7 },
            { id: 'pomodoro_10', condition: stats.totalPomodoros >= 10 },
            { id: 'mood_tracker', condition: stats.moodEntries >= 7 },
            { id: 'gratitude_master', condition: stats.gratitudeEntries >= 10 },
            { id: 'pattern_master', condition: stats.patternGames >= 50 },
            { id: 'zen_master', condition: stats.breathingSessions >= 20 }
        ];

        for (const { id, condition } of toCheck) {
            if (condition && !unlocked.includes(id)) {
                const result = await unlockAchievementDB(id);
                if (result.new) {
                    showAchievementUnlocked(id);
                }
            }
        }

        return unlocked;
    } catch (error) {
        console.error('Error checking achievements:', error);
        return [];
    }
}

function showAchievementUnlocked(id) {
    const achievement = ACHIEVEMENTS[id];
    const notif = document.createElement('div');
    notif.className = 'fixed top-4 right-4 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-6 py-4 rounded-xl shadow-2xl z-50 animate-bounce';
    notif.innerHTML = `
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-4xl">emoji_events</span>
            <div>
                <div class="font-bold">¡Logro Desbloqueado!</div>
                <div class="text-sm">${achievement.name}: ${achievement.desc}</div>
            </div>
        </div>
    `;
    document.body.appendChild(notif);
    setTimeout(() => {
        notif.classList.add('opacity-0', 'transition-opacity');
        setTimeout(() => notif.remove(), 500);
    }, 4000);
}

async function showAchievements() {
    try {
        const unlocked = await getAchievementsDB();

        let achievementHTML = '';
        Object.keys(ACHIEVEMENTS).forEach(id => {
            const ach = ACHIEVEMENTS[id];
            const isUnlocked = unlocked.includes(id);
            achievementHTML += `
                <div class="p-4 rounded-xl ${isUnlocked ? 'bg-gradient-to-br from-' + ach.color + '-500/20 to-' + ach.color + '-600/30 border-2 border-' + ach.color + '-500/40' : 'bg-slate-800/50 opacity-50'} transition-all">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-4xl ${isUnlocked ? 'text-' + ach.color + '-400' : 'text-slate-600'}">${ach.icon}</span>
                        <div class="flex-1">
                            <div class="font-bold text-white">${ach.name}</div>
                            <div class="text-sm text-slate-400">${ach.desc}</div>
                        </div>
                        ${isUnlocked ? '<span class="material-symbols-outlined text-green-500">check_circle</span>' : '<span class="material-symbols-outlined text-slate-600">lock</span>'}
                    </div>
                </div>
            `;
        });

        const modal = `
            <div id="achievementsModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-slate-900 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6 border-b border-slate-700 flex items-center justify-between sticky top-0 bg-slate-900 z-10">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-yellow-500 text-3xl">emoji_events</span>
                            <h2 class="text-2xl font-bold text-white">Logros (${unlocked.length}/${Object.keys(ACHIEVEMENTS).length})</h2>
                        </div>
                        <button onclick="closeModal('achievementsModal')" class="text-slate-400 hover:text-white">
                            <span class="material-symbols-outlined text-3xl">close</span>
                        </button>
                    </div>
                    <div class="p-6 space-y-3">
                        ${achievementHTML}
                    </div>
                </div>
            </div>
        `;

        document.getElementById('modalContainer').innerHTML = modal;
    } catch (error) {
        console.error('Error showing achievements:', error);
        mostrarAdvertencia('Error al cargar logros');
    }
}

// Global statistics
async function getGlobalStats() {
    try {
        return await getStatsDB();
    } catch (error) {
        console.error('Error getting stats:', error);
        return {
            totalActivities: 0,
            todayActivities: 0,
            currentStreak: 0,
            totalPomodoros: 0,
            moodEntries: 0,
            gratitudeEntries: 0,
            avgMood: 0,
            patternGames: 0,
            breathingSessions: 0
        };
    }
}

async function logActivity(type, details = '') {
    try {
        await logActivityDB(type, details);
        await updateDashboardStats();
        await checkAchievements();
    } catch (error) {
        console.error('Error logging activity:', error);
    }
}

async function updateDashboardStats() {
    try {
        const stats = await getGlobalStats();
        const unlocked = await getAchievementsDB();

        const elements = {
            currentStreak: stats.currentStreak,
            todayActivities: stats.todayActivities,
            avgMood: stats.avgMood > 0 ? stats.avgMood + '/5' : '-',
            achievementCount: unlocked.length,
            aspergerProgress: Math.min(100, (stats.patternGames / 50 * 100)).toFixed(0) + '%',
            tdahProgress: Math.min(100, (stats.totalPomodoros / 10 * 100)).toFixed(0) + '%',
            ansiedadProgress: Math.min(100, (stats.breathingSessions / 20 * 100)).toFixed(0) + '%',
            depresionProgress: Math.min(100, (stats.moodEntries / 7 * 100)).toFixed(0) + '%'
        };

        Object.keys(elements).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = elements[id];
        });
    } catch (error) {
        console.error('Error updating dashboard:', error);
    }
}

async function showStats() {
    const stats = await getGlobalStats();

    const modal = `
        <div id="statsModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-900 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-blue-500 text-3xl">analytics</span>
                        <h2 class="text-2xl font-bold text-white">Estadísticas Globales</h2>
                    </div>
                    <button onclick="closeModal('statsModal')" class="text-slate-400 hover:text-white">
                        <span class="material-symbols-outlined text-3xl">close</span>
                    </button>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gradient-to-br from-orange-500/20 to-orange-600/30 border-2 border-orange-500/40 rounded-xl p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-orange-500 text-3xl">local_fire_department</span>
                            <div>
                                <div class="text-3xl font-bold text-white">${stats.currentStreak || 0}</div>
                                <div class="text-sm text-slate-400">Racha actual (días)</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500/20 to-blue-600/30 border-2 border-blue-500/40 rounded-xl p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-blue-500 text-3xl">check_circle</span>
                            <div>
                                <div class="text-3xl font-bold text-white">${stats.totalActivities || 0}</div>
                                <div class="text-sm text-slate-400">Actividades totales</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-orange-500/20 to-orange-600/30 border-2 border-orange-500/40 rounded-xl p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-orange-500 text-3xl">schedule</span>
                            <div>
                                <div class="text-3xl font-bold text-white">${stats.totalPomodoros || 0}</div>
                                <div class="text-sm text-slate-400">Pomodoros completados</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-green-500/20 to-green-600/30 border-2 border-green-500/40 rounded-xl p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-green-500 text-3xl">mood</span>
                            <div>
                                <div class="text-3xl font-bold text-white">${stats.avgMood > 0 ? stats.avgMood + '/5' : '-'}</div>
                                <div class="text-sm text-slate-400">Ánimo promedio (7 días)</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500/20 to-purple-600/30 border-2 border-purple-500/40 rounded-xl p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-purple-500 text-3xl">extension</span>
                            <div>
                                <div class="text-3xl font-bold text-white">${stats.patternGames || 0}</div>
                                <div class="text-sm text-slate-400">Juegos de patrones</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500/20 to-blue-600/30 border-2 border-blue-500/40 rounded-xl p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-blue-500 text-3xl">spa</span>
                            <div>
                                <div class="text-3xl font-bold text-white">${stats.breathingSessions || 0}</div>
                                <div class="text-sm text-slate-400">Sesiones de respiración</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-pink-500/20 to-pink-600/30 border-2 border-pink-500/40 rounded-xl p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-pink-500 text-3xl">favorite</span>
                            <div>
                                <div class="text-3xl font-bold text-white">${stats.gratitudeEntries || 0}</div>
                                <div class="text-sm text-slate-400">Entradas de gratitud</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-green-500/20 to-green-600/30 border-2 border-green-500/40 rounded-xl p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-green-500 text-3xl">sentiment_satisfied</span>
                            <div>
                                <div class="text-3xl font-bold text-white">${stats.moodEntries || 0}</div>
                                <div class="text-sm text-slate-400">Registros de ánimo</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('modalContainer').innerHTML = modal;
}

function exportData() {
    const data = {
        moods: JSON.parse(localStorage.getItem('moods') || '[]'),
        gratitudes: JSON.parse(localStorage.getItem('gratitudes') || '[]'),
        wins: JSON.parse(localStorage.getItem('wins') || '[]'),
        routines: JSON.parse(localStorage.getItem('routines') || '[]'),
        quickTasks: JSON.parse(localStorage.getItem('quickTasks') || '[]'),
        worries: JSON.parse(localStorage.getItem('worries') || '[]'),
        pomodoroData: JSON.parse(localStorage.getItem('pomodoroData') || '{}'),
        achievements: JSON.parse(localStorage.getItem('achievements') || '[]'),
        activityLog: JSON.parse(localStorage.getItem('activityLog') || '[]'),
        exportDate: new Date().toISOString()
    };

    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `bienestar-mental-${new Date().toISOString().split('T')[0]}.json`;
    a.click();
    URL.revokeObjectURL(url);

    mostrarExito('Datos exportados correctamente');
}

// Ambient sounds
function playAmbientSound(type) {
    if (ambientAudio) {
        ambientAudio.pause();
        ambientAudio = null;
    }

    if (type === 'none' || !AMBIENT_SOUNDS[type]) {
        currentAmbientSound = null;
        updateSoundIndicator(null);
        return;
    }

    ambientAudio = new Audio(AMBIENT_SOUNDS[type]);
    ambientAudio.loop = true;
    ambientAudio.volume = 0.3;
    ambientAudio.play().catch(e => {
        console.log('Audio play failed:', e);
        mostrarAdvertencia('No se pudo reproducir el sonido. Interactúa con la página primero.');
    });
    currentAmbientSound = type;
    updateSoundIndicator(type);
}

function updateSoundIndicator(soundType) {
    // Actualizar todos los botones de sonido
    document.querySelectorAll('[data-sound]').forEach(btn => {
        btn.classList.remove('ring-4', 'ring-blue-500', 'bg-blue-500/20');
    });

    // Marcar el botón activo
    if (soundType) {
        const activeBtn = document.querySelector(`[data-sound="${soundType}"]`);
        if (activeBtn) {
            activeBtn.classList.add('ring-4', 'ring-blue-500', 'bg-blue-500/20');
        }
    }

    // Actualizar indicador de sonido actual
    const indicator = document.getElementById('currentSoundIndicator');
    if (indicator) {
        indicator.textContent = soundType ? `🎵 ${SOUND_LABELS[soundType]}` : '🔇 Sin sonido';
    }
}

// Funciones de notificación
function mostrarExito(mensaje) {
    console.log('✅ ' + mensaje);
    // Crear notificación visual temporal
    const notif = document.createElement('div');
    notif.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in';
    notif.textContent = mensaje;
    document.body.appendChild(notif);
    setTimeout(() => {
        notif.classList.add('opacity-0', 'transition-opacity');
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

function mostrarInfo(mensaje) {
    console.log('ℹ️ ' + mensaje);
    const notif = document.createElement('div');
    notif.className = 'fixed top-4 right-4 bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in';
    notif.textContent = mensaje;
    document.body.appendChild(notif);
    setTimeout(() => {
        notif.classList.add('opacity-0', 'transition-opacity');
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

function mostrarAdvertencia(mensaje) {
    console.log('⚠️ ' + mensaje);
    const notif = document.createElement('div');
    notif.className = 'fixed top-4 right-4 bg-yellow-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in';
    notif.textContent = mensaje;
    document.body.appendChild(notif);
    setTimeout(() => {
        notif.classList.add('opacity-0', 'transition-opacity');
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Abrir sección específica
function openSection(section) {
    const modals = {
        'asperger': createAspergerModal,
        'tdah': createTDAHModal,
        'ansiedad': createAnsiedadModal,
        'depresion': createDepresionModal
    };

    if (modals[section]) {
        modals[section]();
    }
}

// ============================================
// ASPERGER: Patrones y Enfoque
// ============================================
function createAspergerModal() {
    const modal = `
        <div id="aspergerModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-white">extension</span>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Asperger - Patrones y Enfoque</h2>
                    </div>
                    <button onclick="closeModal('aspergerModal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <span class="material-symbols-outlined text-3xl">close</span>
                    </button>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Pattern Matching Game -->
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">🧩 Juego de Patrones</h3>
                        <div id="patternGame" class="grid grid-cols-4 gap-3"></div>
                        <button onclick="generatePattern()" class="mt-4 px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
                            Nuevo Patrón
                        </button>
                        <p id="patternScore" class="mt-2 text-sm text-slate-600 dark:text-slate-400"></p>
                    </div>
                    
                    <!-- Focus Timer -->
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">⏱️ Temporizador de Enfoque</h3>
                        <div class="text-center">
                            <div id="focusTimer" class="text-6xl font-bold text-purple-500 mb-4">25:00</div>
                            <div class="flex gap-2 justify-center">
                                <button onclick="startFocusTimer()" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
                                    Iniciar
                                </button>
                                <button onclick="pauseFocusTimer()" class="px-4 py-2 bg-slate-500 text-white rounded-lg hover:bg-slate-600">
                                    Pausar
                                </button>
                                <button onclick="resetFocusTimer()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                    Reiniciar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Routine Tracker -->
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">📋 Tracker de Rutinas</h3>
                        <div id="routineList" class="space-y-2"></div>
                        <div class="flex gap-2 mt-4">
                            <input type="text" id="newRoutine" placeholder="Nueva rutina..." class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <button onclick="addRoutine()" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
                                Agregar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('modalContainer').innerHTML = modal;
    loadRoutines();
    generatePattern();
}

// Pattern Game
let patternColors = ['bg-red-500', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-purple-500', 'bg-pink-500'];
let currentPattern = [];
let patternScore = 0;

function generatePattern() {
    const gameDiv = document.getElementById('patternGame');
    currentPattern = [];
    gameDiv.innerHTML = '';

    for (let i = 0; i < 16; i++) {
        const color = patternColors[Math.floor(Math.random() * patternColors.length)];
        currentPattern.push(color);

        const cell = document.createElement('div');
        cell.className = `${color} h-16 rounded-lg cursor-pointer hover:opacity-80 transition-opacity`;
        cell.onclick = () => {
            cell.classList.add('ring-4', 'ring-white');
            setTimeout(() => cell.classList.remove('ring-4', 'ring-white'), 300);
            patternScore++;
            document.getElementById('patternScore').textContent = `Clics: ${patternScore}`;
        };
        gameDiv.appendChild(cell);
    }

    // Track pattern game
    incrementCounterDB('pattern_games');
    logActivity('pattern_game', 'Nuevo patrón generado');
}

// Focus Timer
let focusInterval;
let focusSeconds = 1500; // 25 minutos

function startFocusTimer() {
    if (focusInterval) return;

    focusInterval = setInterval(() => {
        focusSeconds--;
        updateFocusDisplay();

        if (focusSeconds <= 0) {
            clearInterval(focusInterval);
            focusInterval = null;
            mostrarExito('¡Sesión de enfoque completada! 🎉');
            logActivity('focus_session', '25 minutos de enfoque');
            focusSeconds = 1500;
            updateFocusDisplay();
        }
    }, 1000);
}

function pauseFocusTimer() {
    if (focusInterval) {
        clearInterval(focusInterval);
        focusInterval = null;
    }
}

function resetFocusTimer() {
    pauseFocusTimer();
    focusSeconds = 1500;
    updateFocusDisplay();
}

function updateFocusDisplay() {
    const mins = Math.floor(focusSeconds / 60);
    const secs = focusSeconds % 60;
    document.getElementById('focusTimer').textContent =
        `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

// Routine Tracker
function loadRoutines() {
    const routines = JSON.parse(localStorage.getItem('routines') || '[]');
    const listDiv = document.getElementById('routineList');
    listDiv.innerHTML = '';

    routines.forEach((routine, index) => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 p-3 bg-white dark:bg-slate-800 rounded-lg';
        div.innerHTML = `
            <input type="checkbox" ${routine.done ? 'checked' : ''} onchange="toggleRoutine(${index})" class="w-5 h-5 text-purple-500 rounded">
            <span class="${routine.done ? 'line-through text-slate-400' : 'text-slate-800 dark:text-white'}">${routine.text}</span>
            <button onclick="deleteRoutine(${index})" class="ml-auto text-red-500 hover:text-red-600">
                <span class="material-symbols-outlined">delete</span>
            </button>
        `;
        listDiv.appendChild(div);
    });
}

function addRoutine() {
    const input = document.getElementById('newRoutine');
    const text = input.value.trim();

    if (text) {
        const routines = JSON.parse(localStorage.getItem('routines') || '[]');
        routines.push({ text, done: false });
        localStorage.setItem('routines', JSON.stringify(routines));
        input.value = '';
        loadRoutines();
        logActivity('routine_added', text);
    }
}

function toggleRoutine(index) {
    const routines = JSON.parse(localStorage.getItem('routines') || '[]');
    routines[index].done = !routines[index].done;
    localStorage.setItem('routines', JSON.stringify(routines));
    loadRoutines();
}

function deleteRoutine(index) {
    const routines = JSON.parse(localStorage.getItem('routines') || '[]');
    routines.splice(index, 1);
    localStorage.setItem('routines', JSON.stringify(routines));
    loadRoutines();
}

// ============================================
// TDAH: Enfoque y Recompensas
// ============================================
function createTDAHModal() {
    const modal = `
        <div id="tdahModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-white">bolt</span>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">TDAH - Enfoque y Recompensas</h2>
                    </div>
                    <button onclick="closeModal('tdahModal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <span class="material-symbols-outlined text-3xl">close</span>
                    </button>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Pomodoro Timer -->
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">🍅 Pomodoro (25min trabajo / 5min descanso)</h3>
                        <div class="text-center">
                            <div id="pomodoroTimer" class="text-6xl font-bold text-orange-500 mb-4">25:00</div>
                            <div id="pomodoroStatus" class="text-sm text-slate-600 dark:text-slate-400 mb-4">Listo para empezar</div>
                            <div class="flex gap-2 justify-center">
                                <button onclick="startPomodoro()" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                                    Iniciar
                                </button>
                                <button onclick="pausePomodoro()" class="px-4 py-2 bg-slate-500 text-white rounded-lg hover:bg-slate-600">
                                    Pausar
                                </button>
                                <button onclick="resetPomodoro()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                    Reiniciar
                                </button>
                            </div>
                            <div class="mt-4">
                                <p class="text-sm text-slate-600 dark:text-slate-400">Pomodoros completados hoy: <span id="pomodoroCount" class="font-bold text-orange-500">0</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Tasks -->
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">⚡ Tareas Rápidas (Dopamina Instantánea)</h3>
                        <div id="quickTasksList" class="space-y-2"></div>
                        <div class="flex gap-2 mt-4">
                            <input type="text" id="newQuickTask" placeholder="Nueva tarea rápida..." class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <button onclick="addQuickTask()" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                                Agregar
                            </button>
                        </div>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Tareas completadas: <span id="tasksCompleted" class="font-bold text-orange-500">0</span></p>
                    </div>
                    
                    <!-- Fidget Simulator -->
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">🎮 Simulador Fidget</h3>
                        <div class="flex gap-4 justify-center flex-wrap">
                            <button onclick="fidgetClick(this)" class="w-24 h-24 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full hover:scale-110 transition-transform active:scale-95 shadow-lg">
                                Click!
                            </button>
                            <button onclick="fidgetClick(this)" class="w-24 h-24 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full hover:scale-110 transition-transform active:scale-95 shadow-lg">
                                Pop!
                            </button>
                            <button onclick="fidgetClick(this)" class="w-24 h-24 bg-gradient-to-br from-green-400 to-green-600 rounded-full hover:scale-110 transition-transform active:scale-95 shadow-lg">
                                Spin!
                            </button>
                        </div>
                        <p class="mt-4 text-center text-sm text-slate-600 dark:text-slate-400">Clics: <span id="fidgetClicks" class="font-bold text-orange-500">0</span></p>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('modalContainer').innerHTML = modal;
    loadQuickTasks();
    loadPomodoroCount();
}

// Pomodoro Timer
let pomodoroInterval;
let pomodoroSeconds = 1500; // 25 minutos
let pomodoroMode = 'work'; // 'work' o 'break'

function startPomodoro() {
    if (pomodoroInterval) return;

    document.getElementById('pomodoroStatus').textContent = pomodoroMode === 'work' ? '💪 Trabajando...' : '☕ Descansando...';

    pomodoroInterval = setInterval(() => {
        pomodoroSeconds--;
        updatePomodoroDisplay();

        if (pomodoroSeconds <= 0) {
            clearInterval(pomodoroInterval);
            pomodoroInterval = null;

            if (pomodoroMode === 'work') {
                // Completó trabajo, iniciar descanso
                incrementPomodoroCount();
                mostrarExito('¡Pomodoro completado! Toma un descanso de 5 minutos 🎉');
                pomodoroMode = 'break';
                pomodoroSeconds = 300; // 5 minutos
            } else {
                // Completó descanso, volver a trabajo
                mostrarInfo('Descanso terminado. ¡Listo para otro Pomodoro!');
                pomodoroMode = 'work';
                pomodoroSeconds = 1500; // 25 minutos
            }

            updatePomodoroDisplay();
            document.getElementById('pomodoroStatus').textContent = 'Listo para empezar';
        }
    }, 1000);
}

function pausePomodoro() {
    if (pomodoroInterval) {
        clearInterval(pomodoroInterval);
        pomodoroInterval = null;
        document.getElementById('pomodoroStatus').textContent = '⏸️ Pausado';
    }
}

function resetPomodoro() {
    pausePomodoro();
    pomodoroMode = 'work';
    pomodoroSeconds = 1500;
    updatePomodoroDisplay();
    document.getElementById('pomodoroStatus').textContent = 'Listo para empezar';
}

function updatePomodoroDisplay() {
    const mins = Math.floor(pomodoroSeconds / 60);
    const secs = pomodoroSeconds % 60;
    document.getElementById('pomodoroTimer').textContent =
        `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

function incrementPomodoroCount() {
    incrementPomodoroCountDB();
    loadPomodoroCount();
}

async function loadPomodoroCount() {
    try {
        const data = await getPomodoroDataDB();
        const today = new Date().toISOString().split('T')[0];
        const count = data[today] || 0;
        const el = document.getElementById('pomodoroCount');
        if (el) el.textContent = count;
    } catch (error) {
        console.error('Error loading pomodoro count:', error);
    }
}

// Quick Tasks
function loadQuickTasks() {
    const tasks = JSON.parse(localStorage.getItem('quickTasks') || '[]');
    const listDiv = document.getElementById('quickTasksList');
    listDiv.innerHTML = '';

    let completed = 0;

    tasks.forEach((task, index) => {
        if (task.done) completed++;

        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 p-3 bg-white dark:bg-slate-800 rounded-lg';
        div.innerHTML = `
            <input type="checkbox" ${task.done ? 'checked' : ''} onchange="toggleQuickTask(${index})" class="w-5 h-5 text-orange-500 rounded">
            <span class="${task.done ? 'line-through text-slate-400' : 'text-slate-800 dark:text-white'}">${task.text}</span>
            <button onclick="deleteQuickTask(${index})" class="ml-auto text-red-500 hover:text-red-600">
                <span class="material-symbols-outlined">delete</span>
            </button>
        `;
        listDiv.appendChild(div);
    });

    document.getElementById('tasksCompleted').textContent = completed;
}

function addQuickTask() {
    const input = document.getElementById('newQuickTask');
    const text = input.value.trim();

    if (text) {
        const tasks = JSON.parse(localStorage.getItem('quickTasks') || '[]');
        tasks.push({ text, done: false });
        localStorage.setItem('quickTasks', JSON.stringify(tasks));
        input.value = '';
        loadQuickTasks();
    }
}

function toggleQuickTask(index) {
    const tasks = JSON.parse(localStorage.getItem('quickTasks') || '[]');
    tasks[index].done = !tasks[index].done;
    localStorage.setItem('quickTasks', JSON.stringify(tasks));

    if (tasks[index].done) {
        mostrarExito('¡Tarea completada! +1 Dopamina 🎉');
    }

    loadQuickTasks();
}

function deleteQuickTask(index) {
    const tasks = JSON.parse(localStorage.getItem('quickTasks') || '[]');
    tasks.splice(index, 1);
    localStorage.setItem('quickTasks', JSON.stringify(tasks));
    loadQuickTasks();
}

// Fidget Simulator
let fidgetClickCount = 0;

function fidgetClick(button) {
    fidgetClickCount++;
    document.getElementById('fidgetClicks').textContent = fidgetClickCount;

    // Animación
    button.style.transform = 'scale(0.9) rotate(180deg)';
    setTimeout(() => {
        button.style.transform = '';
    }, 200);

    // Sonido (opcional)
    playClickSound();
}

function playClickSound() {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    oscillator.frequency.value = 800;
    oscillator.type = 'sine';

    gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);

    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.1);
}

// ============================================
// ANSIEDAD: Calma y Respiración
// ============================================
function createAnsiedadModal() {
    const modal = `
        <div id="ansiedadModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-white">waves</span>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Ansiedad - Calma y Respiración</h2>
                    </div>
                    <button onclick="closeModal('ansiedadModal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <span class="material-symbols-outlined text-3xl">close</span>
                    </button>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Breathing Exercise -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">🫁 Ejercicio de Respiración 4-7-8</h3>
                        <div class="text-center">
                            <div class="w-48 h-48 mx-auto mb-4 relative">
                                <div id="breathingCircle" class="w-full h-full bg-gradient-to-br from-blue-400 to-blue-600 rounded-full"></div>
                            </div>
                            <div id="breathingInstruction" class="text-2xl font-bold text-blue-500 mb-4">Presiona Iniciar</div>
                            <div class="flex gap-2 justify-center">
                                <button onclick="startBreathing()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                    Iniciar
                                </button>
                                <button onclick="stopBreathing()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                    Detener
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ambient Sounds -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">🎵 Sonidos Ambientales</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <button onclick="playAmbientSound('rain_thunder')" data-sound="rain_thunder" class="p-4 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg hover:scale-105 transition-all text-white font-semibold">
                                ⛈️ Lluvia con Rayos
                            </button>
                            <button onclick="playAmbientSound('rain')" data-sound="rain" class="p-4 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg hover:scale-105 transition-all text-white font-semibold">
                                🌧️ Lluvia
                            </button>
                            <button onclick="playAmbientSound('forest')" data-sound="forest" class="p-4 bg-gradient-to-br from-green-400 to-green-600 rounded-lg hover:scale-105 transition-all text-white font-semibold">
                                🌲 Bosque
                            </button>
                            <button onclick="playAmbientSound('none')" data-sound="none" class="p-4 bg-gradient-to-br from-slate-400 to-slate-600 rounded-lg hover:scale-105 transition-all text-white font-semibold">
                                🔇 Silencio
                            </button>
                        </div>
                        <p class="mt-4 text-sm text-center font-semibold" id="currentSoundIndicator">🔇 Sin sonido</p>
                    </div>
                    
                    <!-- Calming Colors -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">🎨 Colores Calmantes</h3>
                        <div class="grid grid-cols-4 gap-3">
                            <button onclick="changeBackground('#3B82F6')" class="h-16 bg-blue-500 rounded-lg hover:scale-105 transition-transform"></button>
                            <button onclick="changeBackground('#10B981')" class="h-16 bg-green-500 rounded-lg hover:scale-105 transition-transform"></button>
                            <button onclick="changeBackground('#8B5CF6')" class="h-16 bg-purple-500 rounded-lg hover:scale-105 transition-transform"></button>
                            <button onclick="changeBackground('#EC4899')" class="h-16 bg-pink-500 rounded-lg hover:scale-105 transition-transform"></button>
                        </div>
                    </div>
                    
                    <!-- Worry Journal -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">📝 Diario de Preocupaciones</h3>
                        <textarea id="worryText" placeholder="Escribe tus preocupaciones aquí... Liberarlas ayuda a reducir la ansiedad." class="w-full h-32 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white resize-none"></textarea>
                        <div class="flex gap-2 mt-4">
                            <button onclick="saveWorry()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                Guardar
                            </button>
                            <button onclick="clearWorry()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('modalContainer').innerHTML = modal;
    loadWorry();
    updateCurrentSoundDisplay();
}

function updateCurrentSoundDisplay() {
    const el = document.getElementById('currentSound');
    if (el) {
        const soundNames = { rain: 'Lluvia', ocean: 'Océano', forest: 'Bosque', none: 'Ninguno' };
        el.textContent = soundNames[currentAmbientSound] || 'Ninguno';
    }
}

// Breathing Exercise
let breathingInterval;
let breathingPhase = 0;

function startBreathing() {
    if (breathingInterval) return;

    const circle = document.getElementById('breathingCircle');
    const instruction = document.getElementById('breathingInstruction');

    breathingPhase = 0;
    let cyclesCompleted = 0;

    breathingInterval = setInterval(() => {
        breathingPhase++;

        if (breathingPhase <= 4) {
            // Inhalar (4 segundos)
            instruction.textContent = `Inhala... ${breathingPhase}`;
            circle.style.transform = `scale(${1 + breathingPhase * 0.1})`;
        } else if (breathingPhase <= 11) {
            // Sostener (7 segundos)
            instruction.textContent = `Sostén... ${breathingPhase - 4}`;
        } else if (breathingPhase <= 19) {
            // Exhalar (8 segundos)
            instruction.textContent = `Exhala... ${breathingPhase - 11}`;
            circle.style.transform = `scale(${1.4 - (breathingPhase - 11) * 0.05})`;
        } else {
            // Reiniciar ciclo
            breathingPhase = 0;
            cyclesCompleted++;

            // Track breathing session every 3 cycles
            if (cyclesCompleted % 3 === 0) {
                incrementCounterDB('breathing_sessions');
                logActivity('breathing_session', '3 ciclos de respiración 4-7-8');
            }
        }
    }, 1000);
}

function stopBreathing() {
    if (breathingInterval) {
        clearInterval(breathingInterval);
        breathingInterval = null;
        document.getElementById('breathingInstruction').textContent = 'Presiona Iniciar';
        document.getElementById('breathingCircle').style.transform = 'scale(1)';
    }
}

function changeBackground(color) {
    document.body.style.backgroundColor = color;
    setTimeout(() => {
        document.body.style.backgroundColor = '';
    }, 5000);
}

// Worry Journal
function saveWorry() {
    const text = document.getElementById('worryText').value;
    if (text.trim()) {
        const worries = JSON.parse(localStorage.getItem('worries') || '[]');
        worries.push({
            text,
            date: new Date().toISOString()
        });
        localStorage.setItem('worries', JSON.stringify(worries));
        mostrarExito('Preocupación guardada. Recuerda: escribirlas ayuda a procesarlas.');
        document.getElementById('worryText').value = '';
    }
}

function clearWorry() {
    document.getElementById('worryText').value = '';
}

function loadWorry() {
    const worries = JSON.parse(localStorage.getItem('worries') || '[]');
    if (worries.length > 0) {
        const lastWorry = worries[worries.length - 1];
        document.getElementById('worryText').value = lastWorry.text;
    }
}

// ============================================
// DEPRESIÓN: Ánimo y Motivación
// ============================================
function createDepresionModal() {
    const modal = `
        <div id="depresionModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-white">sunny</span>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Depresión - Ánimo y Motivación</h2>
                    </div>
                    <button onclick="closeModal('depresionModal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <span class="material-symbols-outlined text-3xl">close</span>
                    </button>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Mood Tracker -->
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">😊 ¿Cómo te sientes hoy?</h3>
                        <div class="flex gap-4 justify-center mb-4">
                            <button onclick="saveMood(5, event)" class="text-6xl hover:scale-110 transition-transform">😄</button>
                            <button onclick="saveMood(4, event)" class="text-6xl hover:scale-110 transition-transform">🙂</button>
                            <button onclick="saveMood(3, event)" class="text-6xl hover:scale-110 transition-transform">😐</button>
                            <button onclick="saveMood(2, event)" class="text-6xl hover:scale-110 transition-transform">😔</button>
                            <button onclick="saveMood(1, event)" class="text-6xl hover:scale-110 transition-transform">😢</button>
                        </div>
                        <div id="moodHistory" class="text-sm text-slate-600 dark:text-slate-400"></div>
                    </div>
                    
                    <!-- Gratitude Journal -->
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">🙏 Diario de Gratitud</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Escribe 3 cosas por las que estés agradecido hoy:</p>
                        <div class="space-y-2">
                            <input type="text" id="gratitude1" placeholder="1. Estoy agradecido por..." class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <input type="text" id="gratitude2" placeholder="2. Estoy agradecido por..." class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <input type="text" id="gratitude3" placeholder="3. Estoy agradecido por..." class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        </div>
                        <button onclick="saveGratitude()" class="mt-4 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                            Guardar
                        </button>
                    </div>
                    
                    <!-- Motivational Quotes -->
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">💪 Frase Motivacional</h3>
                        <div id="motivationalQuote" class="text-lg italic text-slate-700 dark:text-slate-300 mb-4 text-center"></div>
                        <button onclick="generateQuote()" class="w-full px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                            Nueva Frase
                        </button>
                    </div>
                    
                    <!-- Small Wins -->
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">🏆 Pequeños Logros</h3>
                        <div id="winsList" class="space-y-2 mb-4"></div>
                        <div class="flex gap-2">
                            <input type="text" id="newWin" placeholder="Hoy logré..." class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <button onclick="addWin()" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                                Agregar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('modalContainer').innerHTML = modal;
    loadMoodHistory();
    loadGratitude();
    generateQuote();
    loadWins();
}

// Mood Tracker
async function saveMood(level, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    try {
        await saveMoodDB(level);
        mostrarExito('Estado de ánimo guardado. ¡Gracias por compartir!');
        await logActivity('mood_entry', `Ánimo: ${level}/5`);
        await loadMoodHistory();
    } catch (error) {
        console.error('Error saving mood:', error);
        mostrarAdvertencia('Error al guardar estado de ánimo');
    }
}

async function loadMoodHistory() {
    try {
        const moods = await getMoodsDB(7);

        if (moods.length > 0) {
            const avg = (moods.reduce((sum, m) => sum + m.level, 0) / moods.length).toFixed(1);
            const el = document.getElementById('moodHistory');
            if (el) {
                el.textContent = `Promedio últimos 7 días: ${avg}/5 ⭐`;
            }
        }
    } catch (error) {
        console.error('Error loading mood history:', error);
    }
}

// Gratitude Journal
async function saveGratitude() {
    const g1 = document.getElementById('gratitude1').value;
    const g2 = document.getElementById('gratitude2').value;
    const g3 = document.getElementById('gratitude3').value;

    if (g1 || g2 || g3) {
        try {
            await saveGratitudeDB(g1, g2, g3);
            mostrarExito('¡Gratitud guardada! La gratitud es poderosa 🙏');
            await logActivity('gratitude_entry', `${[g1, g2, g3].filter(g => g).length} items`);
            document.getElementById('gratitude1').value = '';
            document.getElementById('gratitude2').value = '';
            document.getElementById('gratitude3').value = '';
        } catch (error) {
            console.error('Error saving gratitude:', error);
            mostrarAdvertencia('Error al guardar gratitud');
        }
    }
}

async function loadGratitude() {
    try {
        const gratitudes = await getGratitudesDB(1);
        if (gratitudes.length > 0) {
            const last = gratitudes[0];
            if (last.items[0]) document.getElementById('gratitude1').value = last.items[0];
            if (last.items[1]) document.getElementById('gratitude2').value = last.items[1];
            if (last.items[2]) document.getElementById('gratitude3').value = last.items[2];
        }
    } catch (error) {
        console.error('Error loading gratitude:', error);
    }
}

// Motivational Quotes
const quotes = [
    "Cada día es una nueva oportunidad para ser mejor.",
    "Eres más fuerte de lo que crees.",
    "Los pequeños pasos también cuentan como progreso.",
    "Está bien no estar bien. Mañana será otro día.",
    "Tu valor no depende de tu productividad.",
    "Mereces amor, especialmente el tuyo propio.",
    "Las tormentas no duran para siempre.",
    "Eres suficiente, tal como eres.",
    "Pedir ayuda es un acto de valentía.",
    "Cada respiración es un nuevo comienzo."
];

function generateQuote() {
    const quote = quotes[Math.floor(Math.random() * quotes.length)];
    document.getElementById('motivationalQuote').textContent = `"${quote}"`;
}

// Small Wins
async function loadWins() {
    try {
        const wins = await getWinsDB(5);
        const listDiv = document.getElementById('winsList');
        if (!listDiv) return;

        listDiv.innerHTML = '';

        wins.forEach((win) => {
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2 p-3 bg-white dark:bg-slate-800 rounded-lg';
            div.innerHTML = `
                <span class="material-symbols-outlined text-green-500">check_circle</span>
                <span class="text-slate-800 dark:text-white">${win.text}</span>
            `;
            listDiv.appendChild(div);
        });
    } catch (error) {
        console.error('Error loading wins:', error);
    }
}

async function addWin() {
    const input = document.getElementById('newWin');
    const text = input.value.trim();

    if (text) {
        try {
            await saveWinDB(text);
            input.value = '';
            await loadWins();
            mostrarExito('¡Logro registrado! Celebra tus victorias 🎉');
        } catch (error) {
            console.error('Error adding win:', error);
            mostrarAdvertencia('Error al guardar logro');
        }
    }
}

// Close Modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.remove();
    }
}
