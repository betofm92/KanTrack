#!/usr/bin/env bash
# =============================================================================
# scripts/build-console.sh
#
# Buildea el frontend (Ember) localmente y deja el dist/ listo para commitear.
# Después de ejecutar este script, el dist/ puede ser pusheado al repo y la VM
# usará docker-compose.prebuilt.yml para servir los estáticos sin buildear.
#
# USO:
#   bash scripts/build-console.sh
#
# OPCIONES:
#   --skip-install   Omite pnpm install (útil si las deps ya están instaladas)
#   --env ENV        Entorno de Ember (default: production)
#
# PREREQUISITOS:
#   - Node >= 18
#   - pnpm instalado (npm install -g pnpm)
#   - Ejecutar desde la raíz del repositorio
# =============================================================================

set -euo pipefail

# ── Colores ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log()    { echo -e "${BLUE}[build-console]${NC} $*"; }
success(){ echo -e "${GREEN}[build-console]${NC} $*"; }
warn()   { echo -e "${YELLOW}[build-console]${NC} $*"; }
error()  { echo -e "${RED}[build-console]${NC} $*" >&2; }

# ── Defaults ─────────────────────────────────────────────────────────────────
SKIP_INSTALL=false
EMBER_ENV="production"

# ── Parseo de argumentos ─────────────────────────────────────────────────────
while [[ $# -gt 0 ]]; do
  case "$1" in
    --skip-install) SKIP_INSTALL=true; shift ;;
    --env)          EMBER_ENV="$2"; shift 2 ;;
    *)              error "Argumento desconocido: $1"; exit 1 ;;
  esac
done

# ── Verificar que estamos en la raíz del repo ─────────────────────────────────
if [[ ! -f "pnpm-workspace.yaml" ]]; then
  error "Ejecuta este script desde la raíz del repositorio."
  exit 1
fi

# ── Verificar herramientas ────────────────────────────────────────────────────
if ! command -v pnpm &>/dev/null; then
  error "pnpm no encontrado. Instálalo con: npm install -g pnpm"
  exit 1
fi

if ! command -v node &>/dev/null; then
  error "node no encontrado. Requiere Node >= 18."
  exit 1
fi

NODE_VERSION=$(node -e "process.stdout.write(process.versions.node.split('.')[0])")
if [[ "$NODE_VERSION" -lt 18 ]]; then
  error "Node >= 18 requerido. Versión actual: $(node --version)"
  exit 1
fi

# ── Instalar dependencias ─────────────────────────────────────────────────────
if [[ "$SKIP_INSTALL" == false ]]; then
  log "Instalando dependencias del workspace..."
  pnpm install --no-frozen-lockfile
else
  warn "Omitiendo pnpm install (--skip-install)"
fi

# ── Build ─────────────────────────────────────────────────────────────────────
log "Iniciando build del console (EMBER_ENV=${EMBER_ENV})..."
log "Esto puede tardar varios minutos..."

cd console

EMBER_ENV="$EMBER_ENV" pnpm run build

cd ..

# ── Verificar que el dist fue generado ────────────────────────────────────────
if [[ ! -d "console/dist" ]] || [[ -z "$(ls -A console/dist)" ]]; then
  error "El build falló: console/dist está vacío o no existe."
  exit 1
fi

DIST_SIZE=$(du -sh console/dist | cut -f1)
DIST_FILES=$(find console/dist -type f | wc -l | tr -d ' ')

success "Build completado exitosamente."
log "  Tamaño: ${DIST_SIZE}"
log "  Archivos: ${DIST_FILES}"
log ""
log "Próximos pasos:"
log "  1. Revisar los cambios en console/dist/"
log "  2. git add console/dist"
log "  3. git commit -m 'chore: update console dist'"
log "  4. git push"
log ""
log "En la VM, para usar el dist pre-buildeado:"
log "  docker compose -f docker-compose.yml -f docker-compose.prebuilt.yml up -d --build console"
