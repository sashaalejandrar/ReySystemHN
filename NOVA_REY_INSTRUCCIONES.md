# 🤖 Nova Rey - Instrucciones de Integración

## Integración Rápida

Para activar Nova Rey en cualquier página de tu sistema, agrega esta línea en el `<head>` o antes del cierre de `</body>`:

```html
<script src="nova_rey.js"></script>
```

## Integración Completa

### Opción 1: En todas las páginas del sistema

Edita tu archivo de header/navbar principal y agrega:

```html
<!-- En el <head> o antes de </body> -->
<script src="nova_rey.js"></script>
```

### Opción 2: Solo en páginas específicas

Agrega el script solo en las páginas donde quieras que aparezca Nova Rey:

```html
<!-- ejemplo: en index.php -->
<script src="nova_rey.js"></script>
```

## Personalización

### Cambiar posición del botón

Edita `nova_rey.js` línea 15-16:

```javascript
// Cambiar de bottom-6 right-6 a la posición deseada
<button id="novaReyBtn" class="fixed bottom-6 right-6 ...">
```

### Cambiar colores

Los colores principales están en gradientes:
- Botón: `from-purple-600 via-pink-600 to-blue-600`
- Header: `from-purple-600 via-pink-600 to-blue-600`

### Desactivar en modo producción

Si quieres desactivar temporalmente, comenta la línea:

```html
<!-- <script src="nova_rey.js"></script> -->
```

## API de Groq

Nova Rey usa Groq AI para respuestas inteligentes. La API key está configurada en `nova_rey_api.php`:

```php
define('GROQ_API_KEY', 'YOUR_GROQ_API_KEY_HERE');
```

**Importante:** Esta API key está incluida. Si necesitas cambiarla, edita la línea 17 de `nova_rey_api.php`.

## Funcionalidades

Nova Rey puede:

✅ Detectar errores del sistema
✅ Analizar inventario
✅ Reportar ventas
✅ Verificar estado de caja
✅ Mostrar recordatorios
✅ Sugerir compras
✅ Responder preguntas con IA (Groq)

## Ejemplo de Integración en index.php

```php
<!DOCTYPE html>
<html>
<head>
    <title>ReySystem</title>
    <!-- Tus estilos existentes -->
</head>
<body>
    <!-- Tu contenido existente -->
    
    <!-- Nova Rey - Agregar antes de </body> -->
    <script src="nova_rey.js"></script>
</body>
</html>
```

## Solución de Problemas

### Nova Rey no aparece
- Verifica que `nova_rey.js` esté en la carpeta raíz
- Revisa la consola del navegador (F12) para errores
- Asegúrate de que TailwindCSS y Material Symbols estén cargados

### Errores de API
- Verifica que `nova_rey_api.php` esté en la carpeta raíz
- Confirma que la sesión esté iniciada
- Revisa la conexión a la base de datos

### Groq AI no responde
- Verifica la API key en `nova_rey_api.php`
- Comprueba la conexión a internet
- Revisa los logs de PHP para errores de cURL

## Soporte

Nova Rey está completamente integrada y lista para usar. No requiere configuración adicional.
