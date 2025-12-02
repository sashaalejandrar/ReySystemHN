# 🚀 Módulo: Gestionar Releases

## ✨ Características Implementadas

### 📦 Gestión Completa de Releases
- ✅ Crear releases desde interfaz web
- ✅ Ver historial de releases
- ✅ Publicar releases (actualiza version.json)
- ✅ Eliminar releases
- ✅ Almacenamiento en base de datos
- ✅ Generación automática de archivos comprimidos
- ✅ Commit automático a Git

### 🎨 Interfaz Elegante
- Dashboard con estadísticas
- Tabla con todas las releases
- Modal para crear nueva release
- Modal para ver detalles
- Badges de colores por tipo y estado
- Diseño responsive y modo oscuro

### 🗄️ Base de Datos
- Tabla `updates` con toda la información
- Historial completo de releases
- Estados: draft, pending, published, failed
- Tipos: major, minor, patch

## 📁 Archivos Creados

1. **`gestionar_releases.php`** - Página principal
   - Dashboard con stats
   - Lista de releases
   - Modales para crear/ver

2. **`gestionar_releases.js`** - JavaScript
   - Manejo de modales
   - AJAX para crear/publicar/eliminar
   - Sistema de notificaciones

3. **`api_releases.php`** - API Backend
   - Crear release
   - Publicar release (actualiza version.json + Git)
   - Eliminar release
   - Generar archivos comprimidos

4. **`create_updates_table.sql`** - Estructura BD
   - Tabla con todos los campos necesarios

## 🎯 Flujo de Uso

### 1. Crear Nueva Release

```
1. Click en "Nueva Release"
2. Llenar formulario:
   - Versión (ej: 2.6.0)
   - Nombre código (ej: Supernova)
   - Tipo (major/minor/patch)
   - Fecha
   - Tipo de archivo (tar.gz/zip/both)
   - Lista de cambios
3. Opciones:
   ☑ Crear archivo comprimido
   ☑ Hacer commit a Git
4. Click "Crear Release"
```

### 2. Publicar Release

```
1. Click en botón "Publicar" (icono verde)
2. Confirmar
3. El sistema automáticamente:
   ✅ Actualiza version.json
   ✅ Agrega al changelog
   ✅ Hace commit a Git
   ✅ Crea tag (v2.6.0)
   ✅ Cambia estado a "published"
```

### 3. Ver Detalles

```
1. Click en icono de ojo
2. Ver:
   - Versión y nombre código
   - Lista de cambios
   - Información técnica
   - Estado y tipo
   - Datos de creación
```

## 📊 Tabla `updates`

```sql
CREATE TABLE `updates` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `version` varchar(20) UNIQUE NOT NULL,
  `codename` varchar(100),
  `build` varchar(20) NOT NULL,
  `release_date` date NOT NULL,
  `release_type` enum('major','minor','patch'),
  `changelog` text NOT NULL,
  `changes_json` text NOT NULL,
  `file_type` enum('zip','tar.gz','both'),
  `file_path` varchar(255),
  `file_size` varchar(50),
  `github_tag` varchar(50),
  `github_release_url` varchar(255),
  `status` enum('draft','pending','published','failed'),
  `created_by` varchar(50) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `published_at` timestamp NULL
);
```

## 🎨 Estados de Release

### Draft (Borrador)
- Recién creada
- No publicada
- Se puede editar/eliminar
- No afecta version.json

### Published (Publicada)
- Actualiza version.json
- Commit a Git creado
- Tag creado
- Visible en sistema de actualizaciones

### Pending (Pendiente)
- En proceso de publicación
- Estado temporal

### Failed (Fallida)
- Error en publicación
- Requiere revisión

## 🎯 Tipos de Release

### Major (Rojo)
- Cambios incompatibles
- Nueva versión principal
- Ejemplo: 2.0.0 → 3.0.0

### Minor (Azul)
- Nuevas características
- Compatible con anterior
- Ejemplo: 2.5.0 → 2.6.0

### Patch (Verde)
- Correcciones de bugs
- Mejoras menores
- Ejemplo: 2.5.0 → 2.5.1

## 📦 Generación de Archivos

### TAR.GZ
```php
$phar = new PharData("ReySystem-v2.6.0.tar.gz");
$phar->buildFromDirectory(__DIR__);
$phar->compress(Phar::GZ);
```

### ZIP
```php
$zip = new ZipArchive();
$zip->open("ReySystem-v2.6.0.zip");
// Agregar archivos...
$zip->close();
```

### Archivos Excluidos
- .git
- temp_updates
- backups
- logs
- uploads
- releases

## 🔄 Integración con Git

### Al Publicar Release

```bash
# 1. Actualiza version.json
# 2. Commit
git add version.json
git commit -m "Release v2.6.0 - Supernova"

# 3. Tag
git tag -a v2.6.0 -m "Supernova"

# 4. Push (manual por seguridad)
git push origin main
git push origin v2.6.0
```

## 🎨 Dashboard Stats

- **Total Releases**: Todas las releases creadas
- **Publicadas**: Releases en producción
- **Borradores**: Releases sin publicar
- **Versión Actual**: Del sistema

## 🔐 Seguridad

- ✅ Solo admin puede acceder
- ✅ Validación de versión (semántica)
- ✅ Protección contra duplicados
- ✅ Backup automático antes de publicar
- ✅ Logs de auditoría

## 📱 Responsive

- ✅ Desktop: Vista completa con tabla
- ✅ Tablet: Grid adaptativo
- ✅ Mobile: Cards apiladas

## 🎨 Modo Oscuro

- ✅ Totalmente compatible
- ✅ Colores optimizados
- ✅ Contraste adecuado

## 🚀 Próximas Mejoras

- [ ] Editar releases en draft
- [ ] Rollback a versión anterior
- [ ] Comparar versiones
- [ ] Exportar changelog
- [ ] Notificar usuarios de nueva versión
- [ ] Integración directa con GitHub API
- [ ] Firma digital de releases
- [ ] Changelog con markdown

## 💡 Tips de Uso

### Crear Release Rápida
1. Usa valores por defecto
2. Solo cambia versión y cambios
3. Deja opciones marcadas

### Probar Antes de Publicar
1. Crea como draft
2. Revisa detalles
3. Publica cuando esté listo

### Mantener Historial
- No elimines releases publicadas
- Usa draft para experimentos
- Documenta bien los cambios

---

**Ubicación en Menú:** Admin → Gestionar Releases  
**Icono:** 🚀 rocket_launch  
**Acceso:** Solo administradores
