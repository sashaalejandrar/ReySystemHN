<?php
/**
 * ReySystem Web Installer
 * Multi-step installation wizard for multi-business multi-branch system
 */

define('INSTALLER_ACCESS', true);
require_once 'install_functions.php';

session_start();

// Check if already installed
if (checkInstallation()) {
    header('Location: ../index.php');
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'test_connection':
            $result = testDatabaseConnection(
                $_POST['host'],
                $_POST['user'],
                $_POST['pass'],
                $_POST['dbname']
            );
            
            if ($result['success']) {
                $_SESSION['db_config'] = [
                    'host' => $_POST['host'],
                    'user' => $_POST['user'],
                    'pass' => $_POST['pass'],
                    'dbname' => $_POST['dbname']
                ];
                $_SESSION['db_connection'] = true;
            }
            
            echo json_encode($result);
            exit;
            
        case 'check_requirements':
            echo json_encode(checkSystemRequirements());
            exit;
            
        case 'install':
            if (!isset($_SESSION['db_connection'])) {
                echo json_encode(['success' => false, 'message' => 'No hay conexión a la base de datos']);
                exit;
            }
            
            $db = $_SESSION['db_config'];
            $conn_result = testDatabaseConnection($db['host'], $db['user'], $db['pass'], $db['dbname']);
            
            if (!$conn_result['success']) {
                echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
                exit;
            }
            
            $conn = $conn_result['connection'];
            
            // Create schema
            $schema_result = createDatabaseSchema($conn);
            if (!$schema_result['success']) {
                echo json_encode($schema_result);
                exit;
            }
            
            // Create business
            $business_result = createFirstBusiness($conn, $_POST['business']);
            if (!$business_result['success']) {
                echo json_encode($business_result);
                exit;
            }
            
            $id_negocio = $business_result['id'];
            
            // Create branch
            $branch_result = createFirstBranch($conn, $id_negocio, $_POST['branch']);
            if (!$branch_result['success']) {
                echo json_encode($branch_result);
                exit;
            }
            
            $id_sucursal = $branch_result['id'];
            
            // Create admin user
            $user_result = createAdminUser($conn, $id_negocio, $id_sucursal, $_POST['admin']);
            if (!$user_result['success']) {
                echo json_encode($user_result);
                exit;
            }
            
            // Migrate existing data if any
            migrateExistingData($conn, $id_negocio, $id_sucursal);
            
            // Mark installation complete
            markInstallationComplete($conn, $_POST['admin']['usuario']);
            
            // Save database config
            saveDatabaseConfig($db['host'], $db['user'], $db['pass'], $db['dbname']);
            
            // Clear session
            unset($_SESSION['db_config']);
            unset($_SESSION['db_connection']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Instalación completada exitosamente',
                'redirect' => '../login.php'
            ]);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReySystem - Instalador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#1152d4",
                        "background-dark": "#101622",
                    },
                    fontFamily: {
                        "display": ["Manrope", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        .step-content { display: none; }
        .step-content.active { display: block; }
        .step-indicator { opacity: 0.5; }
        .step-indicator.active { opacity: 1; }
        .step-indicator.completed { opacity: 1; color: #10b981; }
    </style>
</head>
<body class="bg-background-dark font-display text-gray-200 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-5xl font-black text-white mb-4">ReySystem</h1>
            <p class="text-xl text-gray-400">Instalador Multi-Negocio Multi-Sucursal</p>
            <p class="text-sm text-gray-500 mt-2">Versión 2.0.0</p>
        </div>

        <!-- Progress Steps -->
        <div class="flex justify-between mb-12">
            <div class="step-indicator active flex flex-col items-center" data-step="1">
                <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined">database</span>
                </div>
                <span class="text-xs">Base de Datos</span>
            </div>
            <div class="step-indicator flex flex-col items-center" data-step="2">
                <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined">checklist</span>
                </div>
                <span class="text-xs">Requisitos</span>
            </div>
            <div class="step-indicator flex flex-col items-center" data-step="3">
                <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined">store</span>
                </div>
                <span class="text-xs">Negocio</span>
            </div>
            <div class="step-indicator flex flex-col items-center" data-step="4">
                <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined">location_on</span>
                </div>
                <span class="text-xs">Sucursal</span>
            </div>
            <div class="step-indicator flex flex-col items-center" data-step="5">
                <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined">person</span>
                </div>
                <span class="text-xs">Admin</span>
            </div>
            <div class="step-indicator flex flex-col items-center" data-step="6">
                <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined">check_circle</span>
                </div>
                <span class="text-xs">Finalizar</span>
            </div>
        </div>

        <!-- Installation Steps -->
        <div class="bg-gray-800 rounded-xl p-8 shadow-2xl">
            <!-- Step 1: Database Connection -->
            <div class="step-content active" data-step="1">
                <h2 class="text-2xl font-bold mb-6">Configuración de Base de Datos</h2>
                <p class="text-gray-400 mb-6">Ingresa los datos de conexión a tu base de datos MySQL.</p>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Host</label>
                        <input type="text" id="db_host" value="localhost" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Usuario</label>
                        <input type="text" id="db_user" value="root" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Contraseña</label>
                        <input type="password" id="db_pass" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Nombre de Base de Datos</label>
                        <input type="text" id="db_name" value="tiendasrey" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                </div>
                
                <div id="db_message" class="mt-4 p-4 rounded-lg hidden"></div>
                
                <div class="flex justify-end mt-6">
                    <button onclick="testConnection()" class="px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                        Probar Conexión y Continuar
                    </button>
                </div>
            </div>

            <!-- Step 2: System Requirements -->
            <div class="step-content" data-step="2">
                <h2 class="text-2xl font-bold mb-6">Requisitos del Sistema</h2>
                <p class="text-gray-400 mb-6">Verificando que tu servidor cumple con los requisitos necesarios.</p>
                
                <div id="requirements_list" class="space-y-3 mb-6"></div>
                
                <div class="flex justify-between mt-6">
                    <button onclick="prevStep()" class="px-6 py-3 bg-gray-700 text-white rounded-lg font-semibold hover:bg-gray-600 transition">
                        Anterior
                    </button>
                    <button onclick="nextStep()" id="req_continue" class="px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition" disabled>
                        Continuar
                    </button>
                </div>
            </div>

            <!-- Step 3: Business Setup -->
            <div class="step-content" data-step="3">
                <h2 class="text-2xl font-bold mb-6">Configuración del Negocio</h2>
                <p class="text-gray-400 mb-6">Configura tu primer negocio.</p>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Nombre del Negocio *</label>
                        <input type="text" id="business_name" placeholder="Ej: Tienda Rey" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Tipo de Negocio *</label>
                        <select id="business_type" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                            <option value="abarrotes">Abarrotes</option>
                            <option value="ropa">Ropa</option>
                            <option value="ferreteria">Ferretería</option>
                            <option value="farmacia">Farmacia</option>
                            <option value="restaurante">Restaurante</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Dirección</label>
                        <textarea id="business_address" rows="2" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Teléfono</label>
                            <input type="text" id="business_phone" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Email</label>
                            <input type="email" id="business_email" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">RTN</label>
                            <input type="text" id="business_rtn" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Impuesto (%)</label>
                            <input type="number" id="business_tax" value="15" step="0.01" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between mt-6">
                    <button onclick="prevStep()" class="px-6 py-3 bg-gray-700 text-white rounded-lg font-semibold hover:bg-gray-600 transition">
                        Anterior
                    </button>
                    <button onclick="nextStep()" class="px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                        Continuar
                    </button>
                </div>
            </div>

            <!-- Step 4: Branch Setup -->
            <div class="step-content" data-step="4">
                <h2 class="text-2xl font-bold mb-6">Configuración de Sucursal</h2>
                <p class="text-gray-400 mb-6">Configura tu primera sucursal.</p>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Nombre de la Sucursal *</label>
                        <input type="text" id="branch_name" placeholder="Ej: Sucursal Centro" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Código de Sucursal</label>
                        <input type="text" id="branch_code" value="SUC001" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Dirección</label>
                        <textarea id="branch_address" rows="2" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Teléfono</label>
                            <input type="text" id="branch_phone" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Responsable</label>
                            <input type="text" id="branch_manager" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between mt-6">
                    <button onclick="prevStep()" class="px-6 py-3 bg-gray-700 text-white rounded-lg font-semibold hover:bg-gray-600 transition">
                        Anterior
                    </button>
                    <button onclick="nextStep()" class="px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                        Continuar
                    </button>
                </div>
            </div>

            <!-- Step 5: Admin User -->
            <div class="step-content" data-step="5">
                <h2 class="text-2xl font-bold mb-6">Usuario Administrador</h2>
                <p class="text-gray-400 mb-6">Crea el usuario administrador principal del sistema.</p>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Nombre de Usuario *</label>
                        <input type="text" id="admin_username" placeholder="admin" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Nombre *</label>
                            <input type="text" id="admin_name" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Apellido</label>
                            <input type="text" id="admin_lastname" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Email</label>
                        <input type="email" id="admin_email" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Contraseña *</label>
                        <input type="password" id="admin_password" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Confirmar Contraseña *</label>
                        <input type="password" id="admin_password_confirm" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none" required>
                    </div>
                </div>
                
                <div class="flex justify-between mt-6">
                    <button onclick="prevStep()" class="px-6 py-3 bg-gray-700 text-white rounded-lg font-semibold hover:bg-gray-600 transition">
                        Anterior
                    </button>
                    <button onclick="nextStep()" class="px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                        Continuar
                    </button>
                </div>
            </div>

            <!-- Step 6: Installation -->
            <div class="step-content" data-step="6">
                <h2 class="text-2xl font-bold mb-6">Instalación</h2>
                <p class="text-gray-400 mb-6">Revisa la configuración y completa la instalación.</p>
                
                <div id="install_summary" class="bg-gray-700 rounded-lg p-6 mb-6 space-y-3"></div>
                
                <div id="install_progress" class="hidden">
                    <div class="flex items-center justify-center mb-4">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
                    </div>
                    <p class="text-center text-gray-400">Instalando sistema...</p>
                </div>
                
                <div id="install_message" class="mt-4 p-4 rounded-lg hidden"></div>
                
                <div class="flex justify-between mt-6">
                    <button onclick="prevStep()" id="install_back" class="px-6 py-3 bg-gray-700 text-white rounded-lg font-semibold hover:bg-gray-600 transition">
                        Anterior
                    </button>
                    <button onclick="startInstallation()" id="install_button" class="px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition">
                        Instalar ReySystem
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 6;

        function showStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            document.querySelector(`.step-content[data-step="${step}"]`).classList.add('active');
            
            document.querySelectorAll('.step-indicator').forEach((el, index) => {
                el.classList.remove('active', 'completed');
                if (index + 1 < step) {
                    el.classList.add('completed');
                } else if (index + 1 === step) {
                    el.classList.add('active');
                }
            });
            
            if (step === 2) {
                checkRequirements();
            } else if (step === 6) {
                showInstallSummary();
            }
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                if (validateStep(currentStep)) {
                    currentStep++;
                    showStep(currentStep);
                }
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }

        function validateStep(step) {
            if (step === 3) {
                if (!document.getElementById('business_name').value) {
                    alert('Por favor ingresa el nombre del negocio');
                    return false;
                }
            } else if (step === 4) {
                if (!document.getElementById('branch_name').value) {
                    alert('Por favor ingresa el nombre de la sucursal');
                    return false;
                }
            } else if (step === 5) {
                const username = document.getElementById('admin_username').value;
                const name = document.getElementById('admin_name').value;
                const password = document.getElementById('admin_password').value;
                const confirm = document.getElementById('admin_password_confirm').value;
                
                if (!username || !name || !password) {
                    alert('Por favor completa todos los campos requeridos');
                    return false;
                }
                
                if (password !== confirm) {
                    alert('Las contraseñas no coinciden');
                    return false;
                }
                
                if (password.length < 6) {
                    alert('La contraseña debe tener al menos 6 caracteres');
                    return false;
                }
            }
            return true;
        }

        function testConnection() {
            const host = document.getElementById('db_host').value;
            const user = document.getElementById('db_user').value;
            const pass = document.getElementById('db_pass').value;
            const dbname = document.getElementById('db_name').value;
            
            const messageEl = document.getElementById('db_message');
            messageEl.textContent = 'Probando conexión...';
            messageEl.className = 'mt-4 p-4 rounded-lg bg-blue-900 text-blue-200';
            messageEl.classList.remove('hidden');
            
            fetch('index.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=test_connection&host=${encodeURIComponent(host)}&user=${encodeURIComponent(user)}&pass=${encodeURIComponent(pass)}&dbname=${encodeURIComponent(dbname)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    messageEl.textContent = '✓ ' + data.message;
                    messageEl.className = 'mt-4 p-4 rounded-lg bg-green-900 text-green-200';
                    setTimeout(() => nextStep(), 1000);
                } else {
                    messageEl.textContent = '✗ ' + data.message;
                    messageEl.className = 'mt-4 p-4 rounded-lg bg-red-900 text-red-200';
                }
            });
        }

        function checkRequirements() {
            const listEl = document.getElementById('requirements_list');
            listEl.innerHTML = '<p class="text-gray-400">Verificando requisitos...</p>';
            
            fetch('index.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=check_requirements'
            })
            .then(r => r.json())
            .then(data => {
                let html = '';
                for (let key in data.requirements) {
                    const req = data.requirements[key];
                    const icon = req.status ? 'check_circle' : 'cancel';
                    const color = req.status ? 'text-green-500' : 'text-red-500';
                    html += `
                        <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                            <span>${req.name}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-400">${req.value}</span>
                                <span class="material-symbols-outlined ${color}">${icon}</span>
                            </div>
                        </div>
                    `;
                }
                listEl.innerHTML = html;
                document.getElementById('req_continue').disabled = !data.success;
            });
        }

        function showInstallSummary() {
            const summary = document.getElementById('install_summary');
            summary.innerHTML = `
                <h3 class="font-bold mb-3">Resumen de Configuración</h3>
                <div class="space-y-2 text-sm">
                    <p><strong>Negocio:</strong> ${document.getElementById('business_name').value} (${document.getElementById('business_type').value})</p>
                    <p><strong>Sucursal:</strong> ${document.getElementById('branch_name').value} (${document.getElementById('branch_code').value})</p>
                    <p><strong>Administrador:</strong> ${document.getElementById('admin_username').value} (${document.getElementById('admin_name').value})</p>
                    <p><strong>Base de Datos:</strong> ${document.getElementById('db_name').value}</p>
                </div>
            `;
        }

        function startInstallation() {
            document.getElementById('install_progress').classList.remove('hidden');
            document.getElementById('install_button').disabled = true;
            document.getElementById('install_back').disabled = true;
            
            const data = {
                action: 'install',
                business: {
                    nombre: document.getElementById('business_name').value,
                    tipo_negocio: document.getElementById('business_type').value,
                    direccion: document.getElementById('business_address').value,
                    telefono: document.getElementById('business_phone').value,
                    email: document.getElementById('business_email').value,
                    rtn: document.getElementById('business_rtn').value,
                    impuesto: document.getElementById('business_tax').value
                },
                branch: {
                    nombre: document.getElementById('branch_name').value,
                    codigo: document.getElementById('branch_code').value,
                    direccion: document.getElementById('branch_address').value,
                    telefono: document.getElementById('branch_phone').value,
                    responsable: document.getElementById('branch_manager').value
                },
                admin: {
                    usuario: document.getElementById('admin_username').value,
                    nombre: document.getElementById('admin_name').value,
                    apellido: document.getElementById('admin_lastname').value,
                    email: document.getElementById('admin_email').value,
                    password: document.getElementById('admin_password').value
                }
            };
            
            const formData = new URLSearchParams();
            for (let key in data) {
                if (typeof data[key] === 'object') {
                    for (let subkey in data[key]) {
                        formData.append(`${key}[${subkey}]`, data[key][subkey]);
                    }
                } else {
                    formData.append(key, data[key]);
                }
            }
            
            fetch('index.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData.toString()
            })
            .then(r => r.json())
            .then(result => {
                document.getElementById('install_progress').classList.add('hidden');
                const messageEl = document.getElementById('install_message');
                messageEl.classList.remove('hidden');
                
                if (result.success) {
                    messageEl.textContent = '✓ ' + result.message;
                    messageEl.className = 'mt-4 p-4 rounded-lg bg-green-900 text-green-200';
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 2000);
                } else {
                    messageEl.textContent = '✗ ' + result.message;
                    messageEl.className = 'mt-4 p-4 rounded-lg bg-red-900 text-red-200';
                    if (result.errors && result.errors.length > 0) {
                        messageEl.innerHTML += '<ul class="mt-2 text-xs">';
                        result.errors.forEach(err => {
                            messageEl.innerHTML += `<li>${err}</li>`;
                        });
                        messageEl.innerHTML += '</ul>';
                    }
                    document.getElementById('install_button').disabled = false;
                    document.getElementById('install_back').disabled = false;
                }
            })
            .catch(error => {
                document.getElementById('install_progress').classList.add('hidden');
                const messageEl = document.getElementById('install_message');
                messageEl.classList.remove('hidden');
                messageEl.textContent = '✗ Error: ' + error.message;
                messageEl.className = 'mt-4 p-4 rounded-lg bg-red-900 text-red-200';
                document.getElementById('install_button').disabled = false;
                document.getElementById('install_back').disabled = false;
            });
        }
    </script>
</body>
</html>
