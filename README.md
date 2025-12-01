# 🏪 ReySystem - Sistema de Gestión Empresarial

Sistema completo de gestión para tiendas y negocios desarrollado en PHP con MySQL.

## ✨ Características Principales

### 🔐 Seguridad Avanzada
- **Login Multi-Factor** con múltiples opciones:
  - Autenticación WebAuthn/FIDO2 (huella, Face ID, Windows Hello)
  - PIN de seguridad de 4-6 dígitos
  - Dispositivos de confianza con cookies seguras
  - 2FA tradicional con códigos OTP
- Gestión de llaves de seguridad
- Sistema de sesiones seguras

### 📦 Gestión de Inventario
- Control completo de productos y stock
- Alertas de stock mínimo
- Historial de movimientos
- Categorías y marcas
- Códigos de barras
- Múltiples unidades de medida
- Precios por tipo de cliente

### 💰 Punto de Venta (POS)
- Interfaz moderna y rápida
- Búsqueda inteligente de productos
- Gestión de clientes
- Múltiples formas de pago
- Impresión de tickets
- Descuentos y promociones

### 📊 Contabilidad
- Libro de compras y ventas
- Balance general
- Estado de resultados
- Declaración de ISV
- Conciliación bancaria
- Reportes personalizados

### 👥 Gestión de Clientes
- Registro completo de clientes
- Historial de compras
- Sistema de puntos y recompensas
- Gestión de deudas
- Contratos y cotizaciones

### 📈 Reportes y Análisis
- Dashboard con métricas en tiempo real
- Análisis ABC de productos
- Reportes de ventas mensuales
- Metas de ventas
- Estadísticas de caja

### 🔄 Sistema de Actualizaciones
- Verificación automática de actualizaciones desde GitHub
- Descarga e instalación automática
- Backups automáticos antes de actualizar
- Módulo de gestión de releases
- Integración con GitHub CLI

### 🤖 Inteligencia Artificial
- Diagnóstico automático de código
- Corrección de errores con IA
- Parseo inteligente de facturas
- Enriquecimiento de productos
- Búsqueda inteligente

### 📱 Características Adicionales
- PWA (Progressive Web App)
- Modo oscuro completo
- Responsive design
- Sistema de notificaciones
- Chat interno
- Red social empresarial
- Agenda y calendario
- Gestión de pedidos

## 🚀 Instalación

### Requisitos
- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx
- Extensiones PHP: mysqli, json, session, openssl, zip

### Pasos

1. **Clonar el repositorio**
```bash
git clone https://github.com/sashaalejandrar/ReySystemHN.git
cd ReySystemHN
```

2. **Configurar base de datos**
```bash
# Importar el schema
mysql -u root -p < install/schema.sql
```

3. **Configurar permisos**
```bash
chmod 666 version.json
chmod 777 releases backups uploads logs temp_updates
```

O usar el script automático:
```bash
php setup_permissions.php
```

4. **Configurar variables de entorno**
```bash
cp .env.example .env
# Editar .env con tus API keys
```

5. **Acceder al sistema**
```
http://localhost/ReySystemHN
```

Usuario por defecto: `admin` / Contraseña: `admin123`

## 📖 Documentación

- [Sistema de Login Multi-Factor](LOGIN_MULTIFACTOR_README.md)
- [Módulo de Gestión de Releases](MODULO_GESTIONAR_RELEASES.md)
- [Sistema de Actualizaciones](SISTEMA_ACTUALIZACIONES.md)
- [Configurar GitHub](CONFIGURAR_GITHUB_REPO.md)
- [Solución de Permisos](SOLUCION_PERMISOS.md)
- [Guía Rápida de Releases](GUIA_RAPIDA_RELEASES.md)

## 🔧 Configuración

### API Keys

El sistema utiliza APIs de IA para funcionalidades avanzadas:

1. **Groq AI** - Para procesamiento de lenguaje natural
   - Obtén tu key en: https://console.groq.com/keys
   
2. **Mistral AI** - Para análisis de imágenes y texto
   - Obtén tu key en: https://console.mistral.ai/

Configura las keys en el archivo `.env`:
```env
GROQ_API_KEY=tu_key_aqui
MISTRAL_API_KEY=tu_key_aqui
```

### GitHub CLI (Opcional)

Para usar el sistema de releases automático:

```bash
# Instalar GitHub CLI
sudo apt install gh

# Autenticar
gh auth login
```

## 🎯 Uso

### Crear una Release

1. Ve a **Configuración → Gestionar Releases**
2. Click en **Nueva Release**
3. Completa el formulario
4. Click en **Crear Release**

### Publicar Release

1. En la tabla de releases, click en **Publicar**
2. Esto actualizará `version.json` y creará el commit en Git
3. Si GitHub CLI está configurado, se creará automáticamente en GitHub

### Actualizar el Sistema

1. Ve a **Configuración → Actualizaciones**
2. Click en **Verificar Actualizaciones**
3. Si hay actualizaciones, click en **Instalar**

## 🛠️ Tecnologías

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Frameworks CSS**: Tailwind CSS
- **Librerías JS**: Alpine.js, Chart.js, SweetAlert2
- **APIs**: Groq AI, Mistral AI
- **Herramientas**: GitHub CLI, Composer

## 📝 Changelog

Ver [version.json](version.json) para el historial completo de cambios.

### v2.5.0 - Nova (2025-12-01)
- Sistema de login multi-factor completo
- Autenticación WebAuthn/FIDO2 con biometría
- PIN de seguridad y dispositivos de confianza
- Sistema de actualizaciones desde GitHub
- Módulo de gestión de releases
- Integración completa con GitHub CLI
- Sistema de notificaciones mejorado
- Modo oscuro completo

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto es privado y propietario.

## 👤 Autor

**Sasha Alejandra**
- GitHub: [@sashaalejandrar](https://github.com/sashaalejandrar)
- Email: sashaalejandrar24@gmail.com

## 🙏 Agradecimientos

- A todos los que han contribuido al proyecto
- A las comunidades de PHP y JavaScript
- A los proveedores de APIs de IA

---

⭐ Si te gusta este proyecto, dale una estrella en GitHub!
