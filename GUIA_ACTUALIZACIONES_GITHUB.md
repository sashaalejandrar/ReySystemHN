# 🚀 Guía Completa: Sistema de Actualizaciones con GitHub

## 📋 Tabla de Contenidos
1. [Configuración Inicial](#configuración-inicial)
2. [Crear tu Primer Release](#crear-tu-primer-release)
3. [Actualizar el Sistema](#actualizar-el-sistema)
4. [Troubleshooting](#troubleshooting)

---

## 🔧 Configuración Inicial

### Paso 1: Configurar GitHub

1. **Crea un repositorio en GitHub:**
   ```
   Nombre: ReySystem (o el que prefieras)
   Visibilidad: Privado o Público
   ```

2. **Inicializa Git en tu proyecto:**
   ```bash
   cd /opt/lampp/htdocs/ReySystemDemo
   git init
   git add .
   git commit -m "Initial commit - ReySystem v2.5.0"
   ```

3. **Conecta con GitHub:**
   ```bash
   git remote add origin https://github.com/TU-USUARIO/ReySystem.git
   git branch -M main
   git push -u origin main
   ```

### Paso 2: Configurar el Sistema

1. **Edita `update_config.php`:**
   ```php
   'github' => [
       'user' => 'tu-usuario-github',  // ⬅️ CAMBIA ESTO
       'repo' => 'ReySystem',          // ⬅️ Y ESTO
       'branch' => 'main',
   ],
   ```

2. **Verifica que funcione:**
   - Ve a Configuración → Sistema
   - Click en "Buscar Actualizaciones"
   - Debería conectarse a GitHub

---

## 📦 Crear tu Primer Release

### Método 1: Script Automático (Recomendado)

```bash
./create_release.sh
```

El script te pedirá:
- Nueva versión (ej: 2.6.0)
- Nombre de la release (ej: Supernova)
- Descripción de cambios

### Método 2: Manual

1. **Actualiza `version.json`:**
   ```json
   {
     "version": "2.6.0",
     "build": "20241202",
     "release_date": "2024-12-02",
     "codename": "Supernova"
   }
   ```

2. **Crea commit y tag:**
   ```bash
   git add version.json
   git commit -m "Release v2.6.0 - Supernova"
   git tag -a v2.6.0 -m "Supernova"
   git push origin main
   git push origin v2.6.0
   ```

3. **Crea la Release en GitHub:**
   - Ve a: `https://github.com/TU-USUARIO/ReySystem/releases`
   - Click en "Create a new release"
   - Selecciona el tag `v2.6.0`
   - Título: `v2.6.0 - Supernova`
   - Descripción:
     ```markdown
     ## 🎉 Novedades
     - Nueva característica X
     - Mejora en Y
     - Corrección de bug Z
     
     ## 📦 Instalación
     Descarga el archivo ZIP y extrae en tu servidor.
     ```
   - Click en "Publish release"

---

## 🔄 Actualizar el Sistema

### Desde la Interfaz Web

1. **Accede como Admin:**
   - Login → Configuración → Sistema

2. **Buscar Actualizaciones:**
   - Click en "Buscar Actualizaciones"
   - Si hay nueva versión, aparecerá un mensaje

3. **Descargar e Instalar:**
   - Click en "Descargar e Instalar"
   - Espera a que descargue (puede tardar)
   - El sistema creará un backup automático
   - Se instalará la nueva versión
   - Click en "Recargar Sistema"

### Proceso Automático

El sistema hace:
1. ✅ Descarga el ZIP desde GitHub
2. ✅ Crea backup del sistema actual
3. ✅ Extrae archivos nuevos
4. ✅ Actualiza version.json
5. ✅ Limpia archivos temporales

---

## 🎯 Estructura de Versiones

### Versionado Semántico

```
MAJOR.MINOR.PATCH
  2  . 5  .  0
```

- **MAJOR (2.x.x)**: Cambios incompatibles
- **MINOR (x.5.x)**: Nuevas características
- **PATCH (x.x.0)**: Correcciones de bugs

### Ejemplos

```
v2.5.0 → v2.5.1  (Patch: Bug fix)
v2.5.1 → v2.6.0  (Minor: Nueva característica)
v2.6.0 → v3.0.0  (Major: Cambio importante)
```

---

## 📝 Changelog

### Formato Recomendado

```markdown
## v2.6.0 - Supernova (2024-12-02)

### 🎉 Nuevas Características
- Sistema de notificaciones push
- Dashboard mejorado con gráficas

### 🔧 Mejoras
- Rendimiento optimizado en 30%
- Interfaz más responsiva

### 🐛 Correcciones
- Fix: Error en login con 2FA
- Fix: Problema con dispositivos de confianza

### ⚠️ Cambios Importantes
- Requiere PHP 7.4 o superior
- Nueva tabla en base de datos
```

---

## 🔐 Seguridad

### Backups Automáticos

Cada actualización crea un backup en:
```
/backups/backup_YYYY-MM-DD_HH-MM-SS.zip
```

### Restaurar Backup

```bash
# Extraer backup
unzip backups/backup_2024-12-01_15-30-00.zip -d /opt/lampp/htdocs/ReySystemDemo

# O desde PHP
$zip = new ZipArchive();
$zip->open('backups/backup_2024-12-01_15-30-00.zip');
$zip->extractTo(__DIR__);
$zip->close();
```

---

## 🛠️ Troubleshooting

### Error: "No se pudo conectar a GitHub"

**Solución:**
```bash
# Verifica conectividad
curl -I https://api.github.com

# Verifica configuración
cat update_config.php
```

### Error: "Archivo de actualización no encontrado"

**Solución:**
```bash
# Verifica permisos
chmod 755 temp_updates/
chmod 755 backups/

# Verifica espacio en disco
df -h
```

### Error: "No se pudo extraer el archivo"

**Solución:**
```bash
# Verifica extensión ZIP
php -m | grep zip

# Si no está instalada:
sudo apt-get install php-zip
```

### La actualización no aparece

**Solución:**
1. Verifica que el tag en GitHub sea correcto (v2.6.0)
2. Verifica que la release esté publicada (no draft)
3. Espera unos minutos (caché de GitHub)
4. Verifica version.json local

---

## 📊 Monitoreo

### Ver Logs de Actualización

```bash
tail -f logs/updates.log
```

### Ver Versión Actual

```bash
cat version.json | grep version
```

### Listar Backups

```bash
ls -lh backups/
```

---

## 🎓 Mejores Prácticas

### 1. Siempre Prueba en Desarrollo
```bash
# Crea un ambiente de prueba
cp -r /opt/lampp/htdocs/ReySystemDemo /opt/lampp/htdocs/ReySystemDemo-test
```

### 2. Documenta Cambios
- Usa changelog detallado
- Menciona breaking changes
- Incluye instrucciones de migración

### 3. Versionado Consistente
- Sigue semántico versioning
- No saltes versiones
- Usa tags descriptivos

### 4. Backups Regulares
```bash
# Backup manual antes de actualizar
zip -r backup_manual_$(date +%Y%m%d).zip . -x "*.git*" "temp_updates/*" "backups/*"
```

---

## 🚀 Automatización Avanzada

### GitHub Actions (Opcional)

Crea `.github/workflows/release.yml`:

```yaml
name: Create Release

on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Create Release
        uses: actions/create-release@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          tag_name: ${{ github.ref }}
          release_name: Release ${{ github.ref }}
          draft: false
          prerelease: false
```

---

## 📞 Soporte

### Recursos
- 📖 Documentación: `SISTEMA_ACTUALIZACIONES.md`
- 🐛 Reportar bugs: GitHub Issues
- 💬 Comunidad: GitHub Discussions

### Contacto
- GitHub: @tu-usuario
- Email: tu-email@ejemplo.com

---

**Última actualización:** 2024-12-01  
**Versión de la guía:** 1.0  
**Autor:** ReySystem Team
