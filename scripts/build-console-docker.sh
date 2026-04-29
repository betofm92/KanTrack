#!/usr/bin/env bash
# =============================================================================
# scripts/build-console-docker.sh
#
# Genera el dist/ del frontend usando Docker como entorno de build aislado.
# No requiere instalar Node, pnpm ni ember-cli en la máquina host.
# Solo necesitas Docker Desktop (o Docker Engine) corriendo.
#
# USO:
#   bash scripts/build-console-docker.sh
#
# OPCIONES:
#   --no-cache     Fuerza rebuild completo sin usar cache de Docker
#   --push-ready   Al finalizar muestra los comandos git para commitear
#
# RESULTADO:
#   Genera/actualiza console/dist/ en tu proyecto local.
#   Ese dist/ puede ser commiteado y usado en la VM con el modo pre-built.
# =============================================================================

set -euo pipefail

# ── Colores ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

log()    { echo -e "${BLUE}[build-docker]${NC} $*"; }
success(){ echo -e "${GREEN}[build-docker]${NC} $*"; }
warn()   { echo -e "${YELLOW}[build-docker]${NC} $*"; }
error()  { echo -e "${RED}[build-docker]${NC} $*" >&2; }
step()   { echo -e "${CYAN}[build-docker]${NC} ── $* ──"; }

# ── Defaults ─────────────────────────────────────────────────────────────────
NO_CACHE=""
PUSH_READY=false
IMAGE_NAME="kantrack-console-builder"
CONTAINER_NAME="kantrack-console-build-$$"

# ── Parseo de argumentos ─────────────────────────────────────────────────────
while [[ $# -gt 0 ]]; do
  case "$1" in
    --no-cache)   NO_CACHE="--no-cache"; shift ;;
    --push-ready) PUSH_READY=true; shift ;;
    *)            error "Argumento desconocido: $1"; exit 1 ;;
  esac
done

# ── Verificar que estamos en la raíz del repo ─────────────────────────────────
if [[ ! -f "pnpm-workspace.yaml" ]]; then
  error "Ejecuta este script desde la raíz del repositorio."
  exit 1
fi

# ── Verificar que Docker está disponible ─────────────────────────────────────
if ! command -v docker &>/dev/null; then
  error "Docker no encontrado. Instala Docker Desktop: https://www.docker.com/products/docker-desktop/"
  exit 1
fi

if ! docker info &>/dev/null; then
  error "Docker no está corriendo. Inicia Docker Desktop e intenta de nuevo."
  exit 1
fi

# ── Función de limpieza ───────────────────────────────────────────────────────
cleanup() {
  if docker image inspect "$IMAGE_NAME" &>/dev/null; then
    log "Limpiando imagen temporal..."
    docker image rm "$IMAGE_NAME" &>/dev/null || true
  fi
}
# Limpiar imagen al salir (éxito o error)
trap cleanup EXIT

# ── Paso 1: Build de la imagen ────────────────────────────────────────────────
step "Paso 1/3: Construyendo imagen de build"
log "Dockerfile: console/Dockerfile.builder"
log "Contexto: $(pwd)"
[[ -n "$NO_CACHE" ]] && warn "Modo --no-cache activado: ignorando cache de Docker"

docker build \
  $NO_CACHE \
  -f console/Dockerfile.builder \
  -t "$IMAGE_NAME" \
  --target exporter \
  .

success "Imagen construida: ${IMAGE_NAME}"

# ── Paso 2: Extraer el dist al proyecto local ─────────────────────────────────
step "Paso 2/3: Exportando dist/ al proyecto"

# Limpiar dist anterior si existe
if [[ -d "console/dist" ]]; then
  warn "Eliminando console/dist/ anterior..."
  rm -rf console/dist
fi
mkdir -p console/dist

# Correr el contenedor con el volumen montado para exportar el dist
docker run \
  --rm \
  --name "$CONTAINER_NAME" \
  -v "$(pwd)/console/dist:/output" \
  "$IMAGE_NAME"

# ── Paso 3: Verificar resultado ───────────────────────────────────────────────
step "Paso 3/3: Verificando resultado"

if [[ ! -d "console/dist" ]] || [[ -z "$(ls -A console/dist)" ]]; then
  error "El dist/ está vacío o no fue generado correctamente."
  exit 1
fi

DIST_SIZE=$(du -sh console/dist | cut -f1)
DIST_FILES=$(find console/dist -type f | wc -l | tr -d ' ')
INDEX_EXISTS=$([[ -f "console/dist/index.html" ]] && echo "✓" || echo "✗")

echo ""
success "dist/ generado exitosamente"
log "  Tamaño total : ${DIST_SIZE}"
log "  Archivos     : ${DIST_FILES}"
log "  index.html   : ${INDEX_EXISTS}"
echo ""

# ── Instrucciones finales ─────────────────────────────────────────────────────
if [[ "$PUSH_READY" == true ]]; then
  echo -e "${CYAN}══════════════════════════════════════════════════════${NC}"
  echo -e "${CYAN}  Comandos para commitear y deployar${NC}"
  echo -e "${CYAN}══════════════════════════════════════════════════════${NC}"
  echo ""
  echo "  # 1. Asegúrate de que console/dist/ NO está en .gitignore"
  echo "  #    (comenta la línea '/dist/' en console/.gitignore)"
  echo ""
  echo "  git add console/dist"
  echo "  git commit -m \"chore: update console dist\""
  echo "  git push"
  echo ""
  echo "  # 2. En la VM, usar el modo pre-built:"
  echo "  docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.prebuilt.yml up -d --build console"
  echo ""
  echo -e "${CYAN}══════════════════════════════════════════════════════${NC}"
else
  log "Próximos pasos:"
  log "  1. Comenta '/dist/' en console/.gitignore"
  log "  2. git add console/dist"
  log "  3. git commit -m 'chore: update console dist'"
  log "  4. git push"
  log ""
  log "En la VM, para usar el dist pre-buildeado:"
  log "  docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.prebuilt.yml up -d --build console"
  log ""
  log "  O si usas docker-install.sh, responde 'y' cuando pregunte por modo pre-built."
fi
