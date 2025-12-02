# 🔧 Configurar Repositorio GitHub

## Problema Resuelto ✅

Los archivos ZIP y TAR.GZ ahora se crean correctamente. El problema era de **permisos** en el directorio `releases/`.

## Configuración Necesaria

### 1. Crear Repositorio en GitHub

Ve a https://github.com/new y crea un nuevo repositorio llamado `ReySystem` (o el nombre que prefieras).

### 2. Configurar Remote

```bash
# Agregar el remote de GitHub
git remote add origin https://github.com/TU-USUARIO/ReySystem.git

# Verificar
git remote -v
```

### 3. Hacer el Primer Push

```bash
# Agregar todos los archivos
git add .

# Hacer commit
git commit -m "Initial commit - ReySystem v2.5.0"

# Cambiar a rama main (si es necesario)
git branch -M main

# Push inicial
git push -u origin main
```

## Uso del Módulo de Releases

### Crear Release

1. Ve a **Configuración → Gestionar Releases**
2. Click en **Nueva Release**
3. Completa el formulario:
   - **Versión**: 2.6.0 (formato MAJOR.MINOR.PATCH)
   - **Nombre Código**: Supernova (opcional)
   - **Tipo**: major, minor o patch
   - **Fecha**: Fecha de lanzamiento
   - **Tipo de Archivo**: tar.gz, zip o both
   - **Cambios**: Lista de cambios (uno por línea)
   - ✅ **Crear archivo comprimido**: Genera el archivo automáticamente
4. Click en **Crear Release**

### Publicar Release

1. En la tabla de releases, click en el botón **Publicar** (icono verde)
2. Esto hará:
   - Actualizar `version.json`
   - Crear commit en Git
   - Crear tag (ej: v2.6.0)
   - Push a GitHub
   - Crear release en GitHub (si gh CLI está configurado)

### Subir a GitHub (Manual)

Si una release ya está publicada pero no se subió a GitHub:

1. Click en el botón **Subir a GitHub** (icono morado de nube)
2. Esto creará la release en GitHub con el archivo adjunto

## Verificación

### Permisos del Directorio

```bash
# Verificar permisos
ls -la releases/

# Si hay problemas, arreglar:
sudo chmod 777 releases/
sudo chown -R $USER:$USER releases/
```

### GitHub CLI

```bash
# Verificar instalación
gh --version

# Verificar autenticación
gh auth status

# Si no está autenticado:
gh auth login
```

### Git

```bash
# Verificar configuración
git config --list | grep user

# Configurar si es necesario:
git config --global user.name "Tu Nombre"
git config --global user.email "tu@email.com"
```

## Solución de Problemas

### Error: "Permission denied" al crear archivos

```bash
sudo chmod 777 releases/
sudo chown -R $USER:$USER releases/
```

### Error: "No hay remote configurado"

```bash
git remote add origin https://github.com/TU-USUARIO/ReySystem.git
```

### Error: "GitHub CLI no está autenticado"

```bash
gh auth login
# Sigue las instrucciones en pantalla
```

### Error: "dubious ownership in repository"

```bash
git config --global --add safe.directory /opt/lampp/htdocs/ReySystemDemo
```

## Notas

- Los archivos se crean en el directorio `releases/`
- El formato TAR.GZ es más eficiente (menor tamaño)
- El formato ZIP es más compatible con Windows
- Puedes crear ambos formatos seleccionando "both"
- Los archivos excluyen automáticamente: .git, backups, logs, uploads, node_modules, vendor

## Próximos Pasos

1. ✅ Crear repositorio en GitHub
2. ✅ Configurar remote
3. ✅ Hacer push inicial
4. ✅ Crear tu primera release
5. ✅ Publicar y subir a GitHub

¡Listo! Ahora puedes gestionar releases profesionalmente desde la interfaz web.
