#!/bin/bash

# Script para crear una nueva release en GitHub
# Uso: ./create_release.sh

echo "🚀 ReySystem - Crear Nueva Release"
echo "=================================="
echo ""

# Leer versión actual
CURRENT_VERSION=$(grep -oP '"version":\s*"\K[^"]+' version.json)
echo "📦 Versión actual: v$CURRENT_VERSION"
echo ""

# Solicitar nueva versión
read -p "📝 Nueva versión (ej: 2.6.0): " NEW_VERSION

if [ -z "$NEW_VERSION" ]; then
    echo "❌ Versión no puede estar vacía"
    exit 1
fi

# Solicitar nombre de la release
read -p "📝 Nombre de la release (ej: Supernova): " RELEASE_NAME

# Solicitar descripción
echo "📝 Descripción de cambios (presiona Ctrl+D cuando termines):"
DESCRIPTION=$(cat)

# Actualizar version.json
echo ""
echo "📝 Actualizando version.json..."
BUILD_DATE=$(date +%Y%m%d)
RELEASE_DATE=$(date +%Y-%m-%d)

# Crear backup de version.json
cp version.json version.json.backup

# Actualizar version.json (simplificado - en producción usar jq)
sed -i "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" version.json
sed -i "s/\"build\": \"[0-9]*\"/\"build\": \"$BUILD_DATE\"/" version.json
sed -i "s/\"release_date\": \"[0-9-]*\"/\"release_date\": \"$RELEASE_DATE\"/" version.json
sed -i "s/\"codename\": \"[^\"]*\"/\"codename\": \"$RELEASE_NAME\"/" version.json

echo "✅ version.json actualizado"

# Crear commit
echo ""
echo "📝 Creando commit..."
git add version.json
git commit -m "Release v$NEW_VERSION - $RELEASE_NAME"

# Crear tag
echo "🏷️  Creando tag v$NEW_VERSION..."
git tag -a "v$NEW_VERSION" -m "$RELEASE_NAME"

# Push
echo "⬆️  Subiendo cambios a GitHub..."
git push origin main
git push origin "v$NEW_VERSION"

echo ""
echo "✅ Release v$NEW_VERSION creada exitosamente!"
echo ""
echo "📋 Próximos pasos:"
echo "1. Ve a GitHub: https://github.com/TU-USUARIO/ReySystem/releases"
echo "2. Edita la release v$NEW_VERSION"
echo "3. Agrega la descripción de cambios"
echo "4. Sube el archivo ZIP del sistema (opcional)"
echo "5. Publica la release"
echo ""
echo "💡 Tip: GitHub creará automáticamente un archivo ZIP del código fuente"
