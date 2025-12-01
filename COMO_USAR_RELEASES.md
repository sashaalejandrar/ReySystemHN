# 📦 Cómo Usar el Sistema de Releases

## ✅ Tu Release v2.6.0 Ya Está en GitHub!

Tu primera release ya fue creada exitosamente:
**https://github.com/sashaalejandrar/ReySystemHN/releases/tag/v2.6.0**

## 🎯 Cómo Crear Nuevas Releases

### Paso 1: Crear Release
1. Ve a: http://localhost/ReySystemDemo/gestionar_releases.php
2. Click en **"Nueva Release"**
3. Completa el formulario:
   ```
   Versión: 2.7.0
   Codename: Aurora (opcional)
   Tipo: minor
   Fecha: 2025-12-02
   Tipo de Archivo: tar.gz (o zip, o both)
   Cambios:
   - Nueva funcionalidad X
   - Corrección de bug Y
   - Mejora de rendimiento Z
   ```
4. ✅ Marcar "Crear archivo comprimido"
5. Click en **"Crear Release"**

### Paso 2: Publicar Release
1. En la tabla de releases, busca tu nueva release
2. Click en el botón verde **"Publicar"** (icono de publish)
3. Esto hará automáticamente:
   - ✅ Actualizar `version.json`
   - ✅ Crear commit en Git
   - ✅ Crear tag `v2.7.0`
   - ✅ Push a GitHub
   - ✅ Crear release en GitHub
   - ✅ Subir archivo comprimido

### Paso 3: Verificar
1. Ve a: https://github.com/sashaalejandrar/ReySystemHN/releases
2. Deberías ver tu nueva release con el archivo adjunto

## 🔍 Depuración

Si algo no funciona, revisa los logs:

```bash
# Ver logs de releases
tail -f logs/releases.log

# Ver últimas 50 líneas
tail -50 logs/releases.log
```

## 🐛 Solución de Problemas

### Problema: "No se creó la release en GitHub"

**Solución 1: Verificar logs**
```bash
tail -50 logs/releases.log
```

**Solución 2: Verificar GitHub CLI**
```bash
gh auth status
```

**Solución 3: Crear manualmente**
Si el botón no funciona desde el navegador, usa el script manual:
```bash
/opt/lampp/bin/php test_publish_manual.php
```

### Problema: "Error de permisos"

```bash
php setup_permissions.php
```

### Problema: "Tag ya existe"

Si intentas publicar una versión que ya existe:
```bash
# Eliminar tag local
git tag -d v2.7.0

# Eliminar tag remoto
git push origin :refs/tags/v2.7.0

# Eliminar release en GitHub
gh release delete v2.7.0
```

## 📊 Ver Releases Existentes

```bash
# Ver tags locales
git tag -l

# Ver releases en GitHub
gh release list

# Ver detalles de una release
gh release view v2.6.0
```

## 🔄 Actualizar una Release

Si necesitas actualizar el archivo de una release existente:

1. En la tabla de releases, busca la release publicada
2. Click en el botón morado **"Subir a GitHub"** (icono de nube)
3. Esto actualizará el archivo en GitHub

## 📝 Buenas Prácticas

### Versionado Semántico (SemVer)

- **MAJOR** (X.0.0): Cambios incompatibles con versiones anteriores
  - Ejemplo: 3.0.0
  
- **MINOR** (x.X.0): Nueva funcionalidad compatible
  - Ejemplo: 2.7.0
  
- **PATCH** (x.x.X): Correcciones de bugs
  - Ejemplo: 2.6.1

### Nombres de Código (Codenames)

Usa nombres creativos para tus releases:
- v2.5.0 - Nova
- v2.6.0 - SuperNova
- v2.7.0 - Aurora
- v2.8.0 - Nebula
- v3.0.0 - Galaxy

### Changelog

Sé específico en los cambios:
```
✅ Bueno:
- Agregado sistema de notificaciones en tiempo real
- Corregido error al guardar productos con caracteres especiales
- Mejorado rendimiento de búsqueda en 50%

❌ Malo:
- Mejoras varias
- Correcciones
- Cambios en el sistema
```

## 🚀 Flujo Completo de Ejemplo

```bash
# 1. Crear release v2.7.0 desde la interfaz web
# 2. Publicar desde la interfaz web
# 3. Verificar en GitHub
gh release view v2.7.0

# 4. Ver el archivo
gh release view v2.7.0 --json assets

# 5. Descargar (si necesitas)
gh release download v2.7.0
```

## 📦 Tipos de Archivos

### TAR.GZ (Recomendado)
- Más pequeño (mejor compresión)
- Estándar en Linux
- Preserva permisos de archivos

### ZIP
- Compatible con Windows
- Más fácil de extraer en Windows
- Ligeramente más grande

### BOTH
- Crea ambos formatos
- Usuarios pueden elegir
- Ocupa más espacio

## 🎓 Comandos Útiles

```bash
# Ver todas las releases
gh release list

# Ver detalles de una release
gh release view v2.6.0

# Descargar una release
gh release download v2.6.0

# Eliminar una release
gh release delete v2.6.0

# Ver tags
git tag -l

# Ver commits desde último tag
git log v2.6.0..HEAD --oneline

# Crear tag manualmente
git tag -a v2.7.0 -m "Release v2.7.0"
git push origin v2.7.0
```

## 📚 Recursos

- [GitHub Releases](https://docs.github.com/en/repositories/releasing-projects-on-github)
- [GitHub CLI](https://cli.github.com/manual/)
- [Semantic Versioning](https://semver.org/)

---

¡Listo! Ahora puedes crear y publicar releases profesionalmente 🎉
