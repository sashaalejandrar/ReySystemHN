// API Helper para Bienestar Mental
// Funciones para interactuar con la base de datos

const API_URL = 'api_bienestar_mental.php';

// Helper function para hacer peticiones
async function apiRequest(action, data = {}, method = 'POST') {
    try {
        const formData = new FormData();
        formData.append('action', action);

        for (const key in data) {
            formData.append(key, data[key]);
        }

        const response = await fetch(API_URL, {
            method: method,
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// ============================================
// FUNCIONES DE ÁNIMO
// ============================================
async function saveMoodDB(nivel) {
    return await apiRequest('save_mood', { nivel });
}

async function getMoodsDB(limit = 100) {
    const response = await fetch(`${API_URL}?action=get_moods&limit=${limit}`);
    return await response.json();
}

// ============================================
// FUNCIONES DE GRATITUD
// ============================================
async function saveGratitudeDB(item1, item2, item3) {
    return await apiRequest('save_gratitude', { item1, item2, item3 });
}

async function getGratitudesDB(limit = 100) {
    const response = await fetch(`${API_URL}?action=get_gratitudes&limit=${limit}`);
    return await response.json();
}

// ============================================
// FUNCIONES DE LOGROS
// ============================================
async function saveWinDB(texto) {
    return await apiRequest('save_win', { texto });
}

async function getWinsDB(limit = 100) {
    const response = await fetch(`${API_URL}?action=get_wins&limit=${limit}`);
    return await response.json();
}

// ============================================
// FUNCIONES DE RUTINAS
// ============================================
async function saveRoutineDB(texto) {
    return await apiRequest('save_routine', { texto });
}

async function getRoutinesDB() {
    const response = await fetch(`${API_URL}?action=get_routines`);
    return await response.json();
}

async function toggleRoutineDB(id) {
    return await apiRequest('toggle_routine', { id });
}

async function deleteRoutineDB(id) {
    return await apiRequest('delete_routine', { id });
}

// ============================================
// FUNCIONES DE TAREAS RÁPIDAS
// ============================================
async function saveQuickTaskDB(texto) {
    return await apiRequest('save_quick_task', { texto });
}

async function getQuickTasksDB() {
    const response = await fetch(`${API_URL}?action=get_quick_tasks`);
    return await response.json();
}

async function toggleQuickTaskDB(id) {
    return await apiRequest('toggle_quick_task', { id });
}

async function deleteQuickTaskDB(id) {
    return await apiRequest('delete_quick_task', { id });
}

// ============================================
// FUNCIONES DE PREOCUPACIONES
// ============================================
async function saveWorryDB(texto) {
    return await apiRequest('save_worry', { texto });
}

async function getWorriesDB(limit = 100) {
    const response = await fetch(`${API_URL}?action=get_worries&limit=${limit}`);
    return await response.json();
}

// ============================================
// FUNCIONES DE POMODORO
// ============================================
async function incrementPomodoroCountDB() {
    return await apiRequest('increment_pomodoro', {});
}

async function getPomodoroDataDB() {
    const response = await fetch(`${API_URL}?action=get_pomodoro_data`);
    return await response.json();
}

// ============================================
// FUNCIONES DE ACHIEVEMENTS
// ============================================
async function unlockAchievementDB(achievement_id) {
    return await apiRequest('unlock_achievement', { achievement_id });
}

async function getAchievementsDB() {
    const response = await fetch(`${API_URL}?action=get_achievements`);
    return await response.json();
}

// ============================================
// FUNCIONES DE CONTADORES
// ============================================
async function incrementCounterDB(tipo) {
    return await apiRequest('increment_counter', { tipo });
}

async function getCounterDB(tipo) {
    const response = await fetch(`${API_URL}?action=get_counter&tipo=${tipo}`);
    const data = await response.json();
    return data.value || 0;
}

// ============================================
// FUNCIONES DE ESTADÍSTICAS
// ============================================
async function getStatsDB() {
    const response = await fetch(`${API_URL}?action=get_stats`);
    return await response.json();
}

// ============================================
// REGISTRO DE ACTIVIDADES
// ============================================
async function logActivityDB(tipo, detalles = '') {
    return await apiRequest('log_activity', { tipo, detalles });
}
