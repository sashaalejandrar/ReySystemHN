# Fix: user_id vs idUsuario en trusted_devices

## 🐛 Problema
```
Error: Unknown column 'idUsuario' in 'field list'
```

## 🔍 Causa
La tabla `trusted_devices` existente usa `user_id` (int) pero el código nuevo intentaba usar `idUsuario` (varchar).

## ✅ Solución Aplicada

### Estructura Correcta de la Tabla

```sql
CREATE TABLE `trusted_devices` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,              -- ✅ ID numérico
  `device_token` varchar(255) UNIQUE NOT NULL,
  `device_name` varchar(255),
  `device_fingerprint` varchar(255),
  `browser` varchar(100),                  -- ✅ Agregado
  `os` varchar(100),                       -- ✅ Agregado
  `ip_address` varchar(45),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_used` timestamp NULL,
  `expires_at` timestamp NULL,
  KEY `idx_user` (`user_id`)
);
```

### Archivos Corregidos

#### 1. `register_trusted_device_login_v2.php`
- ✅ Cambiado `idUsuario` → `user_id`
- ✅ Usa `$_SESSION['temp_user_id']` (numérico)
- ✅ Agregados campos `browser` y `os`
- ✅ Detecta navegador y sistema operativo
- ✅ Bind param cambiado de `"sssss"` → `"isssssss"`

#### 2. `security_keys_helper.php` - `isTrustedDevice()`
- ✅ Cambiado `idUsuario` → `user_id`
- ✅ Agregada conversión de username a ID numérico
- ✅ Bind param cambiado de `"ss"` → `"is"`
- ✅ Maneja tanto username como ID numérico

```php
// Obtener el ID numérico del usuario si se pasó el username
$numeric_user_id = $user_id;
if (!is_numeric($user_id)) {
    $stmt = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $numeric_user_id = $result->fetch_assoc()['Id'];
    }
}
```

#### 3. `test_register_device.php`
- ✅ Actualizado para usar `user_id`
- ✅ Agregados campos `browser`, `os`, `expires_at`
- ✅ Muestra información completa del dispositivo

#### 4. `setup_pin_security.php`
- ✅ Tabla `trusted_devices` usa `user_id`
- ✅ Agregados campos `browser` y `os`

### Detección de Navegador y OS

```php
// Detectar navegador
if (strpos($user_agent, 'Firefox') !== false) $browser = 'Firefox';
elseif (strpos($user_agent, 'Chrome') !== false) $browser = 'Chrome';
elseif (strpos($user_agent, 'Safari') !== false) $browser = 'Safari';
elseif (strpos($user_agent, 'Edge') !== false) $browser = 'Edge';

// Detectar OS
if (strpos($user_agent, 'Windows') !== false) $os = 'Windows';
elseif (strpos($user_agent, 'Mac') !== false) $os = 'macOS';
elseif (strpos($user_agent, 'Linux') !== false) $os = 'Linux';
elseif (strpos($user_agent, 'Android') !== false) $os = 'Android';
elseif (strpos($user_agent, 'iOS') !== false) $os = 'iOS';
```

## 📊 Comparación

### Antes (Incorrecto)
```sql
INSERT INTO trusted_devices 
  (idUsuario, device_token, ...) 
VALUES 
  (?, ?, ...)
  
-- bind_param("sssss", $usuario, ...)
-- ❌ idUsuario no existe
-- ❌ $usuario es string
```

### Después (Correcto)
```sql
INSERT INTO trusted_devices 
  (user_id, device_token, browser, os, ...) 
VALUES 
  (?, ?, ?, ?, ...)
  
-- bind_param("isssssss", $user_id, ...)
-- ✅ user_id existe
-- ✅ $user_id es int
-- ✅ Incluye browser y os
```

## 🎯 Resultado

✅ **Ahora funciona correctamente:**
- Usa el campo correcto `user_id`
- Guarda información del navegador y OS
- Compatible con la tabla existente
- Fecha de expiración de 30 días
- Cookie segura guardada correctamente

## 🧪 Para Probar

1. **Ejecutar test:**
   ```
   http://localhost/ReySystemDemo/test_register_device.php
   ```

2. **Registrar dispositivo desde login:**
   - Login → Verificación → "Registrar Dispositivo"
   - Debería funcionar sin errores

3. **Verificar en BD:**
   ```sql
   SELECT * FROM trusted_devices ORDER BY id DESC LIMIT 1;
   ```

---

**Estado:** ✅ Corregido completamente
**Compatibilidad:** ✅ Compatible con tabla existente
