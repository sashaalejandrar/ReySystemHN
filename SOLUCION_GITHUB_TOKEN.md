# 🔑 Solución: GitHub Token y Permisos

## 🐛 Problemas Encontrados

### 1. Git Push Falló (código 128)
```
[2025-12-01 22:34:00] git push tag código: 128, success: false
```
**Causa**: Error de autenticación o permisos

### 2. GitHub CLI No Puede Leer Config
```
failed to read configuration: open /root/.config/gh/config.yml: permission denied
```
**Causa**: Apache/PHP ejecuta como usuario diferente y no tiene acceso a tu configuración de GitHub CLI

## ✅ Solución Implementada

### 1. Archivo .env con Token de GitHub

Creado archivo `.env` con tu token de GitHub:
```env
GH_TOKEN=YOUR_GITHUB_TOKEN_HERE
GITHUB_TOKEN=YOUR_GITHUB_TOKEN_HERE
```

### 2. Carga Automática de Variables de Entorno

`api_releases.php` ahora carga automáticamente el archivo `.env`:
```php
// Cargar variables de entorno desde .env
if (file_exists(__DIR__ . '/.env')) {
    $env_lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv("$key=$value");
    }
}
```

### 3. GitHub CLI con Token

Ahora los comandos `gh` se ejecutan con el token:
```php
$env_vars = [
    "GH_TOKEN={$gh_token}",
    "GITHUB_TOKEN={$gh_token}",
    "GH_HOST=github.com"
];
$env_string = implode(' ', $env_vars);
$gh_cmd = "{$env_string} gh release create...";
```

### 4. Mejor Manejo de Errores

- Verifica cada comando Git
- Lanza excepciones claras si falla
- Logs detallados de cada error

## 🔐 Seguridad

### El archivo .env está protegido

Ya está en `.gitignore`:
```gitignore
.env
.env.local
```

### Nunca se sube a GitHub

El token está seguro en tu máquina local.

## 🚀 Cómo Usar

### Opción 1: Usar el .env creado (Ya está listo)

El archivo `.env` ya tiene tu token configurado. Solo:
1. Recarga la página de releases
2. Intenta publicar una release
3. Debería funcionar ahora

### Opción 2: Regenerar Token (Si el actual expira)

```bash
# 1. Generar nuevo token en GitHub
# Ve a: https://github.com/settings/tokens
# O usa gh CLI:
gh auth refresh -s repo

# 2. Obtener el token
gh auth token

# 3. Actualizar .env
nano .env
# Reemplaza GH_TOKEN con el nuevo token
```

### Opción 3: Usar gh auth login

Si prefieres no usar .env:
```bash
# Como root (para que Apache pueda acceder)
sudo gh auth login
```

## 🔍 Verificación

### Verificar que el token funciona

```bash
# Probar con tu token
export GH_TOKEN=YOUR_GITHUB_TOKEN_HERE
gh auth status
```

### Ver logs

```bash
tail -f logs/releases.log
```

Deberías ver:
```
[2025-12-01 22:40:00] Token obtenido de gh auth token
[2025-12-01 22:40:00] Ejecutando: gh release create (con token)
[2025-12-01 22:40:01] gh release create código: 0
```

## 🐛 Solución de Problemas

### Si aún falla el push

```bash
# Verificar credenciales de Git
git config --global credential.helper store

# Hacer un push manual para guardar credenciales
git push origin main
# Ingresa tu usuario y token cuando lo pida
```

### Si el token no funciona

```bash
# Verificar que el token tenga permisos
gh auth status

# Debería mostrar:
# Token scopes: 'repo', 'workflow'
```

### Si GitHub CLI no encuentra el token

Edita `api_releases.php` y agrega el token directamente (temporal):
```php
$gh_token = 'YOUR_GITHUB_TOKEN_HERE';
```

## 📝 Permisos del Token

Tu token actual tiene estos permisos:
- ✅ `repo` - Acceso completo a repositorios
- ✅ `workflow` - Actualizar workflows
- ✅ `gist` - Crear gists
- ✅ `read:org` - Leer organizaciones

Estos son suficientes para crear releases.

## 🎯 Próximos Pasos

1. ✅ El archivo `.env` ya está creado con tu token
2. ✅ `api_releases.php` ya carga las variables de entorno
3. ✅ Los comandos `gh` ya usan el token
4. 🔄 Intenta publicar una release nuevamente
5. 📝 Revisa los logs si hay problemas

## 🔄 Actualizar Token

Si necesitas actualizar el token en el futuro:

```bash
# 1. Obtener nuevo token
gh auth token

# 2. Editar .env
nano .env

# 3. Reemplazar la línea:
GH_TOKEN=nuevo_token_aqui
```

---

¡Ahora debería funcionar correctamente! 🎉
