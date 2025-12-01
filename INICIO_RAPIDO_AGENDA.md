# ⚡ Configuración Rápida - Agenda & Notas

## 🎯 Pasos para Activar el Sistema

### 1️⃣ Configurar PHPMailer (5 minutos)

**Edita:** `api/phpmailer_config.php`

Cambia estas 2 líneas:
```php
$mail->Username   = 'jesushernan.ordo@gmail.com';  // ← TU correo de Gmail
$mail->Password   = 'xxxx xxxx xxxx xxxx';          // ← Contraseña de aplicación
```

### 2️⃣ Obtener Contraseña de Aplicación de Gmail

1. **Ve a:** https://myaccount.google.com/security
2. **Activa:** Verificación en 2 pasos (si no la tienes)
3. **Busca:** "Contraseñas de aplicaciones"
4. **Selecciona:** Correo → Otro (nombre personalizado) → "Rey System"
5. **Copia** la contraseña de 16 caracteres que aparece
6. **Pégala** en `phpmailer_config.php`

### 3️⃣ Agregar Campos a la Tabla Usuarios (Opcional)

Para que la firma electrónica incluya tu email y teléfono:

```sql
ALTER TABLE usuarios ADD COLUMN Email VARCHAR(100) AFTER Apellido;
ALTER TABLE usuarios ADD COLUMN Telefono VARCHAR(20) AFTER Email;
```

Luego actualiza tus datos de usuario.

### 4️⃣ ¡Listo! Prueba el Sistema

1. **Abre:** http://localhost/ReySystemDemo/agenda.php
2. **Ve a:** Pestaña "Correos"
3. **Selecciona:** "🔄 Reabastecer Stock Completo"
4. **Ingresa tu email** como destinatario
5. **Clic en:** "Generar Plantilla"
6. **Clic en:** "Enviar Correo"

**¡Deberías recibir un correo con la lista de productos y tu firma!** 📧

---

## 🚨 Solución de Problemas

### "SMTP connect() failed"
- ✅ Verifica que usaste la **contraseña de aplicación**, NO tu contraseña normal
- ✅ Asegúrate de tener activada la verificación en 2 pasos

### "Could not authenticate"
- ✅ Regenera la contraseña de aplicación
- ✅ Cópiala sin espacios

### No recibo el correo
- ✅ Revisa la carpeta de SPAM
- ✅ Verifica que el correo destinatario sea correcto
- ✅ Revisa el historial de correos en la pestaña "Correos"

---

## 📧 Múltiples Destinatarios

Para enviar a varios correos a la vez:

```
correo1@gmail.com, correo2@gmail.com, correo3@gmail.com
```

Separa con comas (,) y el sistema enviará a todos automáticamente.

---

## 🎨 Características Principales

✅ **Tareas con Kanban** - Organiza tu trabajo
✅ **Notas Personales** - Guarda ideas y recordatorios
✅ **Correos Automáticos** - Plantillas de reabastecimiento de stock
✅ **Firma Electrónica** - Con tus datos de contacto
✅ **Historial Completo** - Todos los correos enviados

---

## 📚 Documentación Completa

- **Guía detallada:** `CONFIGURAR_PHPMAILER.md`
- **Resumen completo:** Ver artifact `agenda_resumen.md`

---

¡Disfruta tu nuevo sistema! 🚀
