# 🚀 Guía Rápida: Crear y Publicar Release

## ⚡ Pasos Rápidos

### 1. Preparar Git (Una sola vez)

```bash
# Asegúrate de estar en el directorio del proyecto
cd /opt/lampp/htdocs/ReySystemDemo

# Inicializar Git si no lo has hecho
git init

# Agregar remote de GitHub
git remote add origin https://github.com/TU-USUARIO/ReySystem.git

# Primer commit
git add .
git commit -m "Initial commit"
git push -u origin main
```

### 2. Instalar GitHub CLI (Opcional pero recomendado)

```bash
# Ubuntu/Debian
sudo apt install gh

# Autenticar
gh auth login
```

### 3. Crear Release desde la Web

1. **Ve a:** Admin → Gestionar Releases
2. **Click:** "Nueva Release"
3. **Llena:**
   - Versión: `2.6.0`
   - Nombre: `Supernova`
   - Tipo: `minor`
   - Cambios: (uno por línea)
     ```
     Nueva característica X
     Mejora en Y
     Fix en Z
     ```
4. **Marca:**
   - ☑ Crear archivo comprimido
   - ☑ Hacer commit a Git
5. **Click:** "Crear Release"

### 4. Publicar Release

1. **Click:** Botón verde "Publicar" (icono)
2. **Confirmar**
3. **El sistema automáticamente:**
   - ✅ Actualiza `version.json`
   - ✅ Hace commit
   - ✅ Crea tag `v2.6.0`
   - ✅ Push a GitHub
   - ✅ Crea release en GitHub (si tienes `gh` CLI)
   - ✅ Sube el archivo tar.gz/zip

## 🔍 Verificar

### En la Consola del Navegador (F12)
```javascript
// Deberías ver:
Release creada: {success: true, file_path: "...", ...}
Release publicada: {success: true, git_success: true, ...}
```

### En GitHub
```
https://github.com/TU-USUARIO/ReySystem/releases
```

Deberías ver tu release con el archivo adjunto.

## 🐛 Troubleshooting

### "No se creó el archivo tar.gz"

**Verifica permisos:**
```bash
chmod 755 releases/
ls -la releases/
```

**Prueba manual:**
```bash
./create_release_tarball.sh
```

### "No se subió a GitHub"

**Verifica Git:**
```bash
git status
git remote -v
```

**Verifica GitHub CLI:**
```bash
gh auth status
```

**Push manual:**
```bash
git push origin main
git push origin v2.6.0

# Crear release manual
gh release create v2.6.0 releases/ReySystem-v2.6.0.tar.gz \
  --title "v2.6.0 - Supernova" \
  --notes "Cambios importantes"
```

### "Error al crear archivo"

**Verifica que tar esté instalado:**
```bash
which tar
tar --version
```

**Verifica espacio en disco:**
```bash
df -h
```

## 📝 Notas Importantes

### Archivos Excluidos Automáticamente
- `.git/`
- `temp_updates/`
- `backups/`
- `logs/`
- `uploads/`
- `releases/`
- `node_modules/`
- `vendor/`

### Formato de Versión
- ✅ Correcto: `2.6.0`, `3.0.0`, `2.5.1`
- ❌ Incorrecto: `v2.6.0`, `2.6`, `version-2.6.0`

### Tipos de Release
- **major**: Cambios incompatibles (2.0.0 → 3.0.0)
- **minor**: Nuevas características (2.5.0 → 2.6.0)
- **patch**: Correcciones (2.5.0 → 2.5.1)

## 🎯 Flujo Completo

```
1. Crear Release (Draft)
   ↓
2. Revisar en lista
   ↓
3. Publicar
   ↓
4. Sistema actualiza version.json
   ↓
5. Git commit + tag
   ↓
6. Push a GitHub
   ↓
7. Crear release en GitHub
   ↓
8. Subir archivo
   ↓
9. ✅ Listo!
```

## 💡 Tips

- **Prueba primero:** Crea como draft, revisa, luego publica
- **Documenta bien:** Escribe cambios claros y descriptivos
- **Versiona correctamente:** Sigue semántico versioning
- **Revisa la consola:** Siempre abre F12 para ver logs

---

**¿Problemas?** Abre la consola (F12) y busca errores en rojo.
