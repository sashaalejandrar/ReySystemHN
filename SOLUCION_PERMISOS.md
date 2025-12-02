# 🔧 Solución: Error de Permisos en version.json

## Problema

```
Error: No se puede escribir en version.json. Verifica permisos.
```

## Causa

El servidor web (Apache/XAMPP) no tiene permisos para escribir en el archivo `version.json`.

## Solución Rápida

### Opción 1: Script Automático

```bash
php setup_permissions.php
```

Si hay errores, ejecutar con sudo:

```bash
sudo php setup_permissions.php
```

### Opción 2: Manual

```bash
# Dar permisos de escritura a version.json
chmod 666 version.json

# Dar permisos a directorios necesarios
chmod 777 releases backups uploads logs temp_updates

# Verificar
ls -la version.json
ls -la releases/
```

## Verificación

Después de cambiar los permisos, verifica que funcione:

```bash
php setup_permissions.php
```

Deberías ver:
```
✅ Todos los permisos están configurados correctamente
✅ version.json es escribible
✅ releases/ es escribible
```

## Mejora Implementada

El sistema ahora intenta cambiar los permisos automáticamente:

```php
// Intentar cambiar permisos automáticamente
if (@chmod('version.json', 0666)) {
    clearstatcache(true, 'version.json');
} else {
    throw new Exception('No se puede escribir en version.json. Ejecuta: chmod 666 version.json');
}
```

## Permisos Recomendados

| Archivo/Directorio | Permisos | Descripción |
|-------------------|----------|-------------|
| `version.json` | 666 | Lectura/escritura para todos |
| `releases/` | 777 | Directorio para archivos de release |
| `backups/` | 777 | Directorio para backups |
| `uploads/` | 777 | Directorio para archivos subidos |
| `logs/` | 777 | Directorio para logs |
| `temp_updates/` | 777 | Directorio temporal para actualizaciones |

## Seguridad

Los permisos `666` y `777` son necesarios para que el servidor web pueda escribir, pero ten en cuenta:

- ✅ Está bien en desarrollo local (XAMPP)
- ⚠️ En producción, considera usar el usuario del servidor web
- 🔒 Asegúrate de que estos directorios no sean accesibles públicamente

### Alternativa Más Segura (Producción)

```bash
# Cambiar propietario al usuario del servidor web
sudo chown -R www-data:www-data version.json releases/ backups/ uploads/ logs/ temp_updates/

# Permisos más restrictivos
chmod 644 version.json
chmod 755 releases/ backups/ uploads/ logs/ temp_updates/
```

## Troubleshooting

### Error: "Operación no permitida"

Si ves este error al ejecutar `chmod`:

```bash
# Usar sudo
sudo chmod 666 version.json
sudo chmod 777 releases backups uploads logs temp_updates
```

### Error persiste después de cambiar permisos

1. Verificar que el archivo existe:
   ```bash
   ls -la version.json
   ```

2. Limpiar caché de PHP:
   ```bash
   sudo /opt/lampp/lampp restart
   ```

3. Verificar propietario:
   ```bash
   ls -la version.json
   # Si el propietario es root, cambiar:
   sudo chown $USER:$USER version.json
   ```

### Verificar usuario del servidor web

```bash
ps aux | grep httpd | head -1
# o
ps aux | grep apache | head -1
```

## Automatización

Para evitar este problema en el futuro, agrega esto a tu script de instalación:

```bash
#!/bin/bash
# setup.sh

echo "Configurando permisos..."
chmod 666 version.json
chmod 777 releases backups uploads logs temp_updates

echo "Verificando..."
php setup_permissions.php

echo "¡Listo!"
```

## Próximos Pasos

1. ✅ Ejecutar `php setup_permissions.php`
2. ✅ Verificar que todos los permisos estén correctos
3. ✅ Intentar publicar una release nuevamente
4. ✅ Verificar que `version.json` se actualiza correctamente

¡Problema resuelto! 🎉
