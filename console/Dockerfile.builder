# console/Dockerfile.builder
#
# Imagen de BUILD AISLADO: genera el dist/ del frontend dentro de Docker
# y lo exporta a la carpeta local del proyecto via volumen montado.
#
# NO se usa en producción. Solo se invoca desde scripts/build-console-docker.sh
# para generar console/dist/ sin instalar Node/pnpm en la máquina host.
#
# Uso directo (preferir el script):
#   docker build -f console/Dockerfile.builder -t console-builder .
#   docker run --rm -v "$(pwd)/console/dist:/output" console-builder

FROM node:20-alpine AS builder

WORKDIR /app

# Dependencias de compilación nativas (para módulos con bindings)
RUN apk add --no-cache git python3 make g++

# Habilitar pnpm via corepack (viene con Node 20)
RUN corepack enable

# Copiar manifests del workspace primero (mejor cache de capas)
COPY pnpm-workspace.yaml package.json ./

# Copiar packages locales y el console
COPY packages ./packages
COPY console ./console

# Instalar todas las dependencias del workspace
RUN pnpm install --no-frozen-lockfile

# Sanity check: los packages críticos deben existir
RUN test -d /app/packages/dev-engine && test -d /app/packages/ember-core

# Build de producción
WORKDIR /app/console
RUN pnpm run build

# Etapa de exportación: copia el dist al directorio de salida montado
FROM alpine:3.20 AS exporter
COPY --from=builder /app/console/dist /dist
# El entrypoint copia /dist al volumen montado en /output
CMD ["sh", "-c", "cp -r /dist/. /output/ && echo 'dist exportado correctamente'"]
