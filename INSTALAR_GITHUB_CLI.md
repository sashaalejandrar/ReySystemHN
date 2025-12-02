# 🚀 Instalar GitHub CLI para Subir Releases Automáticamente

## ¿Qué es GitHub CLI?

GitHub CLI (`gh`) es una herramienta de línea de comandos que permite interactuar con GitHub directamente desde la terminal. Con ella, puedes crear releases, issues, pull requests y más.

## 📦 Instalación

### En Ubuntu/Debian (Linux)

```bash
# Método 1: Desde repositorio oficial (Recomendado)
type -p curl >/dev/null || (sudo apt update && sudo apt install curl -y)
curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg \
&& sudo chmod go+r /usr/share/keyrings/githubcli-archive-keyring.gpg \
&& echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null \
&& sudo apt update \
&& sudo apt install gh -y

# Método 2: Desde snap
sudo snap install gh

# Método 3: Desde apt (si está disponible)
sudo apt install gh
```

### En macOS

```bash
# Con Homebrew
brew install gh

# Con MacPorts
sudo port install gh
```

### En Windows

```powershell
# Con Chocolatey
choco install gh

# Con Scoop
scoop install gh

# Con winget
winget install --id GitHub.cli
```

## 🔐 Autenticación

Después de instalar, debes autenticarte con tu cuenta de GitHub:

```bash
# Iniciar autenticación
gh auth login

# Selecciona:
# 1. GitHub.com
# 2. HTTPS
# 3. Login with a web browser (o paste an authentication token)
# 4. Sigue las instrucciones en el navegador
```

### Verificar Autenticación

```bash
# Ver estado de autenticación
gh auth status

# Debería mostrar:
# ✓ Logged in to github.com as TU-USUARIO
```

## ✅ Verificar Instalación

```bash
# Ver versión
gh --version

# Debería mostrar algo como:
# gh version 2.40.0 (2024-01-01)
```

## 🎯 Uso con ReySystem

Una vez instalado y autenticado, el sistema automáticamente:

1. ✅ Crea el commit
2. ✅ Crea el tag
3. ✅ Hace push a GitHub
4. ✅ Crea la release en GitHub
5. ✅ Sube el archivo comprimido
6. ✅ Agrega el changelog

### Publicar Release

```
1. Ve a Gestionar Releases
2. Crea una nueva release
3. Click en "Publicar"
4. ¡Listo! Se sube automáticamente a GitHub
```

## 🔧 Comandos Útiles

```bash
# Ver releases del repositorio
gh release list

# Ver detalles de una release
gh release view v2.6.0

# Crear release manualmente
gh release create v2.6.0 \
  --title "v2.6.0 - Supernova" \
  --notes "Nueva versión con mejoras" \
  archivo.tar.gz

# Eliminar release
gh release delete v2.6.0

# Ver repositorio en el navegador
gh repo view --web
```

## 🐛 Troubleshooting

### Error: "gh: command not found"

```bash
# Verifica la instalación
which gh

# Si no está instalado, instala de nuevo
sudo apt install gh
```

### Error: "authentication required"

```bash
# Autentica de nuevo
gh auth login

# O usa un token
gh auth login --with-token < token.txt
```

### Error: "repository not found"

```bash
# Verifica que estás en el directorio correcto
pwd

# Verifica el remote de Git
git remote -v

# Debería mostrar tu repositorio de GitHub
```

### Error: "permission denied"

```bash
# Verifica permisos del token
gh auth status

# Reautentica con permisos completos
gh auth refresh -h github.com -s repo
```

## 📝 Configuración Adicional

### Configurar Editor por Defecto

```bash
gh config set editor nano
# o
gh config set editor vim
```

### Configurar Navegador

```bash
gh config set browser firefox
```

### Ver Configuración

```bash
gh config list
```

## 🎓 Recursos

- [Documentación Oficial](https://cli.github.com/manual/)
- [GitHub CLI en GitHub](https://github.com/cli/cli)
- [Guía de Inicio Rápido](https://docs.github.com/en/github-cli/github-cli/quickstart)

## ⚡ Alternativa: Sin GitHub CLI

Si no quieres instalar GitHub CLI, puedes:

1. El sistema hace commit y push automáticamente
2. Ve manualmente a GitHub
3. Crea la release desde la interfaz web
4. Sube el archivo desde `releases/ReySystem-vX.X.X.tar.gz`

---

**Recomendación:** Instala GitHub CLI para automatización completa 🚀
