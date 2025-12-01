<?php
header('Content-Type: text/plain');

echo "=== DIAGNÓSTICO DE PYTHON ===\n\n";

// Verificar qué Python está usando
$pythonPath = shell_exec('which python3 2>&1');
echo "Python path: " . $pythonPath . "\n";

// Verificar versión
$pythonVersion = shell_exec('python3 --version 2>&1');
echo "Python version: " . $pythonVersion . "\n";

// Verificar usuario
$user = shell_exec('whoami 2>&1');
echo "Usuario ejecutando PHP: " . $user . "\n";

// Verificar si aiohttp está instalado
$aiohttp = shell_exec('python3 -c "import aiohttp; print(aiohttp.__version__)" 2>&1');
echo "aiohttp: " . $aiohttp . "\n";

// Verificar beautifulsoup4
$bs4 = shell_exec('python3 -c "import bs4; print(bs4.__version__)" 2>&1');
echo "beautifulsoup4: " . $bs4 . "\n";

// Verificar requests
$requests = shell_exec('python3 -c "import requests; print(requests.__version__)" 2>&1');
echo "requests: " . $requests . "\n";

// Probar script directamente
echo "\n=== PRUEBA DE SCRIPT ===\n";
$output = shell_exec('python3 /opt/lampp/htdocs/ReySystemDemo/python/scraper_async_mistral.py "test" "" 2>&1');
echo "Output: " . $output . "\n";
?>
