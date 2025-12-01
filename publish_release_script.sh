#!/bin/bash
# Script para publicar release - evita problemas de librerías de PHP

RELEASE_ID=$1
DB_NAME="tiendasrey"
DB_USER="root"
DB_PASS=""

# Cambiar al directorio del proyecto
cd /opt/lampp/htdocs/ReySystemDemo

echo "=== Publicando Release ID: $RELEASE_ID ==="

# Configurar safe.directory primero
sudo git config --system --add safe.directory /opt/lampp/htdocs/ReySystemDemo 2>/dev/null || git config --global --add safe.directory /opt/lampp/htdocs/ReySystemDemo 2>/dev/null || true

# Obtener datos de la release
QUERY="SELECT version, codename, build, release_date, changes_json, file_path FROM updates WHERE id=$RELEASE_ID"
RELEASE_DATA=$(/opt/lampp/bin/mysql -u $DB_USER $DB_NAME -N -e "$QUERY")

if [ -z "$RELEASE_DATA" ]; then
    echo "ERROR: Release no encontrada"
    exit 1
fi

# Parsear datos
VERSION=$(echo "$RELEASE_DATA" | cut -f1)
CODENAME=$(echo "$RELEASE_DATA" | cut -f2)
BUILD=$(echo "$RELEASE_DATA" | cut -f3)
RELEASE_DATE=$(echo "$RELEASE_DATA" | cut -f4)
CHANGES_JSON=$(echo "$RELEASE_DATA" | cut -f5)
FILE_PATH=$(echo "$RELEASE_DATA" | cut -f6)

GIT_TAG="v$VERSION"

echo "Versión: $VERSION"
echo "Codename: $CODENAME"
echo "Tag: $GIT_TAG"

# Leer token desde .env
if [ ! -f .env ]; then
    echo "ERROR: Archivo .env no encontrado"
    exit 1
fi

GH_TOKEN=$(grep "^GH_TOKEN=" .env | cut -d= -f2)

if [ -z "$GH_TOKEN" ]; then
    echo "ERROR: No se encontró GH_TOKEN en .env"
    exit 1
fi

echo "Token encontrado: ${GH_TOKEN:0:15}..."

# Configurar usuario de Git
git config user.name "ReySystem Bot" 2>/dev/null || true
git config user.email "reysystem@localhost" 2>/dev/null || true

# Actualizar version.json
echo "Actualizando version.json..."
# Aquí PHP ya debería haberlo actualizado, solo verificamos
if [ ! -f version.json ]; then
    echo "ERROR: version.json no encontrado"
    exit 1
fi

# URL con token para push
PUSH_URL="https://sashaalejandrar:$GH_TOKEN@github.com/sashaalejandrar/ReySystemHN.git"

# 1. Add version.json
echo "1. git add version.json"
git add version.json
if [ $? -ne 0 ]; then
    echo "ERROR: git add falló"
    exit 1
fi

# 2. Commit
echo "2. git commit"
COMMIT_MSG="Release $GIT_TAG - $CODENAME"
git commit -m "$COMMIT_MSG"
COMMIT_CODE=$?
if [ $COMMIT_CODE -ne 0 ] && [ $COMMIT_CODE -ne 1 ]; then
    echo "ERROR: git commit falló con código $COMMIT_CODE"
    exit 1
fi

# 3. Tag
echo "3. git tag"
git tag -a $GIT_TAG -m "$CODENAME" 2>/dev/null
if [ $? -ne 0 ]; then
    echo "Tag ya existe, eliminando y recreando..."
    git tag -d $GIT_TAG
    git tag -a $GIT_TAG -m "$CODENAME"
fi

# 4. Push usando API de GitHub (evita problemas de librerías)
echo "4. Subiendo cambios a GitHub con API..."

# Obtener el SHA del commit actual
COMMIT_SHA=$(git rev-parse HEAD)
echo "Commit SHA: $COMMIT_SHA"

# Actualizar la referencia main usando la API
curl -X PATCH \
  -H "Authorization: token $GH_TOKEN" \
  -H "Accept: application/vnd.github.v3+json" \
  https://api.github.com/repos/sashaalejandrar/ReySystemHN/git/refs/heads/main \
  -d "{\"sha\":\"$COMMIT_SHA\",\"force\":true}" \
  -s -o /tmp/push_main.json

if grep -q "\"ref\"" /tmp/push_main.json; then
    echo "✓ Main actualizado en GitHub"
else
    echo "ERROR: No se pudo actualizar main"
    cat /tmp/push_main.json
    exit 1
fi

# 5. Crear/actualizar tag usando API
echo "5. Creando tag en GitHub..."

# Obtener SHA del tag object
TAG_SHA=$(git rev-parse $GIT_TAG^{})

# Crear tag object en GitHub
curl -X POST \
  -H "Authorization: token $GH_TOKEN" \
  -H "Accept: application/vnd.github.v3+json" \
  https://api.github.com/repos/sashaalejandrar/ReySystemHN/git/tags \
  -d "{\"tag\":\"$GIT_TAG\",\"message\":\"$CODENAME\",\"object\":\"$COMMIT_SHA\",\"type\":\"commit\"}" \
  -s -o /tmp/create_tag.json

TAG_OBJECT_SHA=$(grep -o '"sha":"[^"]*"' /tmp/create_tag.json | head -1 | cut -d'"' -f4)

if [ -z "$TAG_OBJECT_SHA" ]; then
    # Tag ya existe, obtener su SHA
    TAG_OBJECT_SHA=$(git rev-parse $GIT_TAG)
fi

# Crear/actualizar referencia del tag
curl -X POST \
  -H "Authorization: token $GH_TOKEN" \
  -H "Accept: application/vnd.github.v3+json" \
  https://api.github.com/repos/sashaalejandrar/ReySystemHN/git/refs \
  -d "{\"ref\":\"refs/tags/$GIT_TAG\",\"sha\":\"$COMMIT_SHA\"}" \
  -s -o /tmp/create_ref.json 2>&1

# Si falla porque ya existe, actualizar
if grep -q "Reference already exists" /tmp/create_ref.json; then
    curl -X PATCH \
      -H "Authorization: token $GH_TOKEN" \
      -H "Accept: application/vnd.github.v3+json" \
      https://api.github.com/repos/sashaalejandrar/ReySystemHN/git/refs/tags/$GIT_TAG \
      -d "{\"sha\":\"$COMMIT_SHA\",\"force\":true}" \
      -s -o /tmp/update_ref.json
fi

echo "✓ Tag subido a GitHub"

# 6. Crear release en GitHub con gh CLI
if command -v gh &> /dev/null; then
    echo "6. Creando release en GitHub..."
    
    # Crear notas desde changes_json
    NOTES_FILE=$(mktemp)
    echo "## 🎉 Novedades" > $NOTES_FILE
    echo "" >> $NOTES_FILE
    
    # Parsear JSON de cambios (simple)
    echo "$CHANGES_JSON" | sed 's/\[//g' | sed 's/\]//g' | sed 's/"//g' | sed 's/,/\n/g' | while read line; do
        if [ ! -z "$line" ]; then
            echo "- $line" >> $NOTES_FILE
        fi
    done
    
    echo "" >> $NOTES_FILE
    echo "## 📦 Instalación" >> $NOTES_FILE
    echo "Descarga el archivo adjunto y extrae en tu servidor." >> $NOTES_FILE
    
    # Configurar variables de entorno para gh
    export GH_TOKEN=$GH_TOKEN
    export GITHUB_TOKEN=$GH_TOKEN
    export GH_HOST=github.com
    
    # Crear release
    TITLE="$GIT_TAG - $CODENAME"
    if [ -f "$FILE_PATH" ]; then
        echo "Subiendo con archivo: $FILE_PATH"
        gh release create $GIT_TAG --title "$TITLE" --notes-file $NOTES_FILE "$FILE_PATH"
    else
        echo "Sin archivo adjunto"
        gh release create $GIT_TAG --title "$TITLE" --notes-file $NOTES_FILE
    fi
    
    GH_CODE=$?
    if [ $GH_CODE -eq 0 ]; then
        echo "✓ Release creada en GitHub"
        
        # Obtener URL
        RELEASE_URL=$(gh release view $GIT_TAG --json url -q .url 2>/dev/null)
        
        if [ ! -z "$RELEASE_URL" ]; then
            echo "URL: $RELEASE_URL"
            # Actualizar BD con URL
            /opt/lampp/bin/mysql -u $DB_USER $DB_NAME -e "UPDATE updates SET status='published', published_at=NOW(), github_tag='$GIT_TAG', github_release_url='$RELEASE_URL' WHERE id=$RELEASE_ID"
        else
            # Actualizar BD sin URL
            /opt/lampp/bin/mysql -u $DB_USER $DB_NAME -e "UPDATE updates SET status='published', published_at=NOW(), github_tag='$GIT_TAG' WHERE id=$RELEASE_ID"
        fi
    else
        echo "ERROR: No se pudo crear release en GitHub (código: $GH_CODE)"
        # Actualizar BD de todas formas
        /opt/lampp/bin/mysql -u $DB_USER $DB_NAME -e "UPDATE updates SET status='published', published_at=NOW(), github_tag='$GIT_TAG' WHERE id=$RELEASE_ID"
        exit 1
    fi
    
    rm -f $NOTES_FILE
else
    echo "WARNING: GitHub CLI no instalado"
    # Actualizar BD sin GitHub
    /opt/lampp/bin/mysql -u $DB_USER $DB_NAME -e "UPDATE updates SET status='published', published_at=NOW(), github_tag='$GIT_TAG' WHERE id=$RELEASE_ID"
fi

echo ""
echo "=== ✓ Release publicada exitosamente ==="
exit 0
