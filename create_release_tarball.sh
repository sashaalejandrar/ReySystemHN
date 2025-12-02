#!/bin/bash

# Script para crear tarball de release
# Uso: ./create_release_tarball.sh

echo "📦 ReySystem - Crear Tarball de Release"
echo "========================================"
echo ""

# Leer versión actual
VERSION=$(grep -oP '"version":\s*"\K[^"]+' version.json)
echo "📌 Versión actual: v$VERSION"
echo ""

# Nombre del archivo
FILENAME="ReySystem-v${VERSION}.tar.gz"

# Confirmar
read -p "¿Crear $FILENAME? (s/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "❌ Cancelado"
    exit 1
fi

echo ""
echo "📦 Creando tarball..."
echo ""

# Crear tarball excluyendo archivos innecesarios
tar -czf "$FILENAME" \
  --exclude='.git' \
  --exclude='.gitignore' \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='temp_updates' \
  --exclude='backups' \
  --exclude='logs' \
  --exclude='uploads' \
  --exclude='*.log' \
  --exclude='*.backup' \
  --exclude='*.tmp' \
  --exclude='*.swp' \
  --exclude='.DS_Store' \
  --exclude='Thumbs.db' \
  --exclude='create_release_tarball.sh' \
  --exclude='create_release.sh' \
  --exclude='*.md' \
  --exclude='test_*.php' \
  --exclude='debug_*.php' \
  .

# Verificar que se creó
if [ -f "$FILENAME" ]; then
    SIZE=$(du -h "$FILENAME" | cut -f1)
    echo ""
    echo "✅ Tarball creado exitosamente!"
    echo ""
    echo "📄 Archivo: $FILENAME"
    echo "📊 Tamaño: $SIZE"
    echo ""
    echo "📋 Contenido:"
    tar -tzf "$FILENAME" | head -20
    echo "..."
    echo ""
    echo "🚀 Próximos pasos:"
    echo "1. Sube este archivo a GitHub Release"
    echo "2. O usa: gh release upload v$VERSION $FILENAME"
    echo ""
else
    echo "❌ Error al crear tarball"
    exit 1
fi
