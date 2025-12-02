# 📦 Cómo Crear una Nueva Versión de ReySystem

## 🎯 Pasos Rápidos

### 1. Actualizar version.json

```bash
# Edita version.json
nano version.json
```

```json
{
  "version": "2.6.0",
  "build": "20241202",
  "release_date": "2024-12-02",
  "codename": "Supernova",
  "changelog": [
    {
      "version": "2.6.0",
      "date": "2024-12-02",
      "type": "minor",
      "changes": [
        "Nueva característica X",
        "Mejora en Y",
        "Fix en Z"
      ]
    }
  ]
}
```

### 2. Commit y Push

```bash
git add version.json
git commit -m "Release v2.6.0 - Supernova"
git push origin main
```

### 3. Crear Tag

```bash
git tag -a v2.6.0 -m "Release v2.6.0 - Supernova"
git push origin v2.6.0
```

### 4. Crear Release en GitHub

**Opción A: Desde la Web**
1. Ve a: `https://github.com/TU-USUARIO/ReySystem/releases/new`
2. Selecciona tag: `v2.6.0`
3. Título: `v2.6.0 - Supernova`
4. Descripción:
   ```markdown
   ## 🎉 Novedades
   - Nueva característica X
   - Mejora en Y
   
   ## 🐛 Correcciones
   - Fix en Z
   
   ## 📦 Instalación
   Descarga el archivo y extrae en tu servidor.
   ```
5. **Opcional:** Sube archivo tar.gz o zip
6. Click "Publish release"

**Opción B: Con GitHub CLI**
```bash
gh release create v2.6.0 \
  --title "v2.6.0 - Supernova" \
  --notes "Nueva versión con mejoras importantes"
```

---

## 📦 Trabajar con TAR.GZ

### Crear archivo TAR.GZ para release

```bash
# Script para crear release tar.gz
./create_release_tarball.sh
```

O manualmente:

```bash
# Crear tar.gz excluyendo archivos innecesarios
tar -czf ReySystem-v2.6.0.tar.gz \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='temp_updates' \
  --exclude='backups' \
  --exclude='logs' \
  --exclude='uploads' \
  --exclude='*.log' \
  .
```

### Subir TAR.GZ a GitHub Release

**Desde la web:**
1. Crea la release
2. En "Attach binaries", arrastra `ReySystem-v2.6.0.tar.gz`
3. Publish

**Con GitHub CLI:**
```bash
gh release create v2.6.0 \
  --title "v2.6.0 - Supernova" \
  --notes "Nueva versión" \
  ReySystem-v2.6.0.tar.gz
```

---

## 🔄 El Sistema Detectará Automáticamente

### Archivos que busca:

1. **ZIP** (preferido por GitHub)
   - `ReySystem-v2.6.0.zip`
   - O el zipball automático de GitHub

2. **TAR.GZ** (si lo subes manualmente)
   - `ReySystem-v2.6.0.tar.gz`
   - Necesitas modificar `check_updates.php`

---

## 🛠️ Modificar para Soportar TAR.GZ

Si prefieres usar tar.gz, actualiza `check_updates.php`:

```php
// Buscar archivo en los assets
$download_url = null;
$file_size = null;

if (isset($github_release['assets']) && is_array($github_release['assets'])) {
    foreach ($github_release['assets'] as $asset) {
        // Buscar .tar.gz primero, luego .zip
        if (strpos($asset['name'], '.tar.gz') !== false || 
            strpos($asset['name'], '.zip') !== false) {
            $download_url = $asset['browser_download_url'];
            $file_size = $asset['size'];
            break;
        }
    }
}
```

---

## 📝 Checklist Completo

### Antes de Crear Release

- [ ] Todos los cambios están commiteados
- [ ] Tests pasando (si tienes)
- [ ] version.json actualizado
- [ ] Changelog actualizado
- [ ] README actualizado (si es necesario)

### Crear Release

- [ ] Commit de version.json
- [ ] Push a main
- [ ] Crear tag (v2.6.0)
- [ ] Push tag
- [ ] Crear release en GitHub
- [ ] Agregar descripción detallada
- [ ] Subir archivo tar.gz/zip (opcional)
- [ ] Publicar release

### Después de Release

- [ ] Probar actualización en ambiente de prueba
- [ ] Verificar que el sistema detecta la actualización
- [ ] Actualizar documentación
- [ ] Anunciar nueva versión

---

## 🎨 Estructura Recomendada

### version.json DEBE estar en el repo

```
ReySystem/
├── version.json          ← SÍ, en el repo
├── check_updates.php     ← SÍ, en el repo
├── update_config.php     ← SÍ, en el repo
├── .gitignore           ← SÍ, en el repo
├── backups/             ← NO, en .gitignore
├── temp_updates/        ← NO, en .gitignore
└── logs/                ← NO, en .gitignore
```

### ¿Por qué version.json en el repo?

1. ✅ El sistema lo lee para saber versión actual
2. ✅ GitHub lo incluye en el ZIP automático
3. ✅ Historial de versiones en Git
4. ✅ Fácil de comparar con versión remota

---

## 🚀 Flujo Completo de Release

```bash
# 1. Actualizar versión
nano version.json

# 2. Commit
git add version.json
git commit -m "Release v2.6.0"

# 3. Tag
git tag -a v2.6.0 -m "v2.6.0 - Supernova"

# 4. Push
git push origin main
git push origin v2.6.0

# 5. Crear tar.gz (opcional)
tar -czf ReySystem-v2.6.0.tar.gz \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='temp_updates' \
  --exclude='backups' \
  --exclude='logs' \
  .

# 6. Crear release en GitHub
gh release create v2.6.0 \
  --title "v2.6.0 - Supernova" \
  --notes-file CHANGELOG.md \
  ReySystem-v2.6.0.tar.gz
```

---

## 💡 Tips

### Versionado Automático

Crea un script `bump-version.sh`:

```bash
#!/bin/bash
CURRENT=$(grep -oP '"version":\s*"\K[^"]+' version.json)
echo "Versión actual: $CURRENT"
read -p "Nueva versión: " NEW
sed -i "s/\"version\": \"$CURRENT\"/\"version\": \"$NEW\"/" version.json
echo "✅ Actualizado a $NEW"
```

### Release Notes Automáticas

```bash
# Generar changelog desde commits
git log v2.5.0..HEAD --pretty=format:"- %s" > RELEASE_NOTES.md
```

### Verificar antes de publicar

```bash
# Ver cambios desde última versión
git diff v2.5.0..HEAD

# Ver archivos que se incluirán
git ls-files
```

---

## 🔍 Troubleshooting

### "No se detecta la actualización"

1. Verifica que el tag esté en GitHub:
   ```bash
   git ls-remote --tags origin
   ```

2. Verifica que la release esté publicada (no draft)

3. Espera 1-2 minutos (caché de GitHub)

### "Error al descargar"

1. Verifica que el archivo exista en la release
2. Verifica permisos de escritura en `temp_updates/`
3. Verifica espacio en disco

### "version.json no se actualiza"

1. Asegúrate de hacer commit del archivo
2. Verifica que esté en el tag:
   ```bash
   git show v2.6.0:version.json
   ```

---

## 📚 Recursos

- [GitHub Releases](https://docs.github.com/en/repositories/releasing-projects-on-github)
- [Semantic Versioning](https://semver.org/)
- [Keep a Changelog](https://keepachangelog.com/)

---

**¿Dudas?** Revisa `GUIA_ACTUALIZACIONES_GITHUB.md`
