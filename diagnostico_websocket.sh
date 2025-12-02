#!/bin/bash
# Script para diagnosticar problemas de WebSocket

echo "=== Diagnóstico de WebSocket ==="
echo ""

echo "1. Verificando si el servidor está corriendo..."
if ps aux | grep -v grep | grep chat_server.php > /dev/null; then
    echo "✅ Servidor chat_server.php está corriendo"
    ps aux | grep -v grep | grep chat_server.php | awk '{print "   PID: "$2", Tiempo: "$10}'
else
    echo "❌ Servidor NO está corriendo"
    echo "   Ejecuta: php chat_server.php"
fi

echo ""
echo "2. Verificando puerto 8080..."
if netstat -tulpn 2>/dev/null | grep 8080 > /dev/null; then
    echo "✅ Puerto 8080 está en uso"
    netstat -tulpn 2>/dev/null | grep 8080 | head -1
else
    echo "❌ Puerto 8080 NO está en uso"
fi

echo ""
echo "3. Probando conexión WebSocket..."
timeout 3 nc -zv localhost 8080 2>&1 | head -1

echo ""
echo "4. Verificando base de datos..."
/opt/lampp/bin/mysql -u root -e "USE tiendasrey; SELECT COUNT(*) as total FROM usuarios;" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✅ Conexión a base de datos OK"
else
    echo "❌ Error conectando a base de datos"
fi

echo ""
echo "5. Verificando logs de Apache..."
if [ -f /opt/lampp/logs/error_log ]; then
    echo "Últimos 5 errores de PHP:"
    tail -5 /opt/lampp/logs/error_log | grep -i "php\|error" || echo "   No hay errores recientes"
fi

echo ""
echo "=== Fin del diagnóstico ==="
