#!/bin/bash
# Script de instalación de dependencias Python

echo "🐍 Instalando dependencias de Python para scrapers..."

# Verificar si Python3 está instalado
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 no está instalado. Por favor instala Python3 primero."
    exit 1
fi

# Verificar si pip está instalado
if ! command -v pip3 &> /dev/null; then
    echo "📦 Instalando pip..."
    sudo apt-get update
    sudo apt-get install -y python3-pip
fi

# Instalar dependencias
echo "📦 Instalando dependencias de Python..."
pip3 install -r /opt/lampp/htdocs/ReySystemDemo/python/requirements.txt

# Instalar ChromeDriver para Selenium (opcional)
echo "🌐 Instalando ChromeDriver para Selenium..."
sudo apt-get install -y chromium-chromedriver

echo "✅ Instalación completada!"
echo ""
echo "📝 Métodos Python disponibles:"
echo "  🚀 Python Selenium - Para sitios con JavaScript pesado"
echo "  ⚡ Python Async - Para scraping rápido y paralelo"
echo "  🧠 Python Smart - Multi-técnica con Mistral AI"
echo ""
echo "🎉 ¡Todo listo para usar los scrapers de Python!"
