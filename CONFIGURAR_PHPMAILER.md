# 📧 Configuración de PHPMailer para Agenda & Notas

## 📋 Requisitos Previos

1. **Cuenta de Gmail** (o cualquier otro proveedor SMTP)
2. **Contraseña de aplicación** de Gmail (NO tu contraseña normal)

---

## 🔐 Paso 1: Crear Contraseña de Aplicación en Gmail

### Para cuentas con verificación en 2 pasos:

1. Ve a tu cuenta de Google: https://myaccount.google.com/
2. Selecciona **Seguridad** en el menú lateral
3. En "Cómo inicias sesión en Google", selecciona **Verificación en 2 pasos**
4. Desplázate hasta el final y selecciona **Contraseñas de aplicaciones**
5. Selecciona la aplicación: **Correo**
6. Selecciona el dispositivo: **Otro (nombre personalizado)**
7. Escribe: **Rey System APP**
8. Haz clic en **Generar**
9. **COPIA LA CONTRASEÑA DE 16 CARACTERES** (la necesitarás en el siguiente paso)

### Si no tienes verificación en 2 pasos:

1. Activa la verificación en 2 pasos primero
2. Luego sigue los pasos anteriores

---

## ⚙️ Paso 2: Configurar PHPMailer

Edita el archivo: `/opt/lampp/htdocs/ReySystemDemo/api/phpmailer_config.php`

```php
$mail->Username   = 'TU_CORREO@gmail.com'; // ← Cambia esto
$mail->Password   = 'xxxx xxxx xxxx xxxx'; // ← Pega aquí la contraseña de aplicación
```

### Ejemplo:
```php
$mail->Username   = 'jesushernan.ordo@gmail.com';
$mail->Password   = 'abcd efgh ijkl mnop'; // Contraseña de aplicación de 16 caracteres
```

---

## 📦 Paso 3: Instalar PHPMailer

### Opción 1: Usando Composer (Recomendado)

```bash
cd /opt/lampp/htdocs/ReySystemDemo
composer require phpmailer/phpmailer
```

### Opción 2: Descarga Manual

1. Descarga PHPMailer desde: https://github.com/PHPMailer/PHPMailer/releases
2. Extrae los archivos en: `/opt/lampp/htdocs/ReySystemDemo/phpmailer/`
3. Asegúrate de tener estos archivos:
   - `phpmailer/PHPMailer.php`
   - `phpmailer/SMTP.php`
   - `phpmailer/Exception.php`

---

## ✅ Paso 4: Probar el Envío

1. Abre la Agenda: `http://localhost/ReySystemDemo/agenda.php`
2. Ve a la pestaña **Correos**
3. Selecciona tipo: **Reabastecer Stock Completo**
4. Ingresa tu correo en **Destinatario(s)**
5. Haz clic en **Generar Plantilla**
6. Haz clic en **Enviar Correo**

Si todo está configurado correctamente, recibirás un correo con:
- ✅ Lista de productos con bajo stock
- ✅ Firma electrónica personalizada con tu nombre
- ✅ Tus datos de contacto (email y teléfono)
- ✅ Timestamp digital

---

## 🔧 Solución de Problemas

### Error: "SMTP connect() failed"

**Causa:** Gmail bloqueó el acceso

**Solución:**
1. Verifica que usaste la **contraseña de aplicación**, NO tu contraseña normal
2. Asegúrate de tener activada la verificación en 2 pasos
3. Intenta permitir "Acceso de aplicaciones menos seguras" (no recomendado)

### Error: "Could not authenticate"

**Causa:** Credenciales incorrectas

**Solución:**
1. Verifica que el correo sea correcto
2. Regenera la contraseña de aplicación
3. Copia y pega la nueva contraseña (sin espacios)

### Error: "Mailer Error: SMTP Error: Could not connect to SMTP host"

**Causa:** Firewall o puerto bloqueado

**Solución:**
1. Verifica que el puerto 587 esté abierto
2. Prueba cambiar el puerto a 465 y `SMTPSecure` a `'ssl'`
3. Desactiva temporalmente el firewall para probar

---

## 🌐 Usar Otro Proveedor SMTP

### Para Outlook/Hotmail:

```php
$mail->Host       = 'smtp-mail.outlook.com';
$mail->Port       = 587;
$mail->Username   = 'tu_correo@outlook.com';
$mail->Password   = 'tu_contraseña';
```

### Para Yahoo:

```php
$mail->Host       = 'smtp.mail.yahoo.com';
$mail->Port       = 587;
$mail->Username   = 'tu_correo@yahoo.com';
$mail->Password   = 'tu_contraseña_de_aplicacion';
```

### Para servidor SMTP personalizado:

```php
$mail->Host       = 'smtp.tudominio.com';
$mail->Port       = 587; // o 465 para SSL
$mail->Username   = 'tu_usuario';
$mail->Password   = 'tu_contraseña';
```

---

## 📝 Notas Importantes

1. **Nunca compartas tu contraseña de aplicación** en repositorios públicos
2. La contraseña de aplicación es específica para esta app
3. Puedes revocar la contraseña en cualquier momento desde tu cuenta de Google
4. Los correos se envían desde tu cuenta, así que los destinatarios verán tu correo como remitente
5. Gmail tiene un límite de **500 correos por día** para cuentas gratuitas

---

## 🎯 Características del Sistema de Correos

✅ **Múltiples destinatarios:** Separa correos con comas
✅ **Plantillas automáticas:** Para reabastecimiento de stock
✅ **Firma electrónica:** Con tus datos de contacto
✅ **Historial completo:** Todos los correos enviados se registran
✅ **Detección de productos sin stock:** Con indicadores visuales
✅ **Selección selectiva:** Elige qué productos incluir
✅ **Timestamp digital:** Fecha y hora de envío

---

## 📞 Soporte

Si tienes problemas, verifica:
1. Los logs de error de PHP: `/opt/lampp/logs/php_error_log`
2. La consola del navegador (F12)
3. El historial de correos en la pestaña Correos

Para más ayuda, contacta al desarrollador del sistema.
