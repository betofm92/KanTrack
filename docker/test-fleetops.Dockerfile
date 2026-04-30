# docker/test-fleetops.Dockerfile
# Imagen dedicada para ejecutar los tests unitarios del paquete fleetops
# sin necesidad de instalar nada en el entorno local.
#
# Uso:
#   docker build -f docker/test-fleetops.Dockerfile -t fleetops-test .
#   docker run --rm fleetops-test
#
# Para desarrollo iterativo (monta el código como volumen):
#   docker run --rm -v ${PWD}:/app fleetops-test

FROM node:20-alpine

WORKDIR /app

# Herramientas de build + Chromium headless para ember test
RUN apk add --no-cache git python3 make g++ \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ca-certificates \
    ttf-freefont

# Apunta ember-cli al Chromium de Alpine
ENV CHROME_BIN=/usr/bin/chromium-browser
ENV CHROMIUM_FLAGS="--no-sandbox --disable-gpu --disable-dev-shm-usage"
ENV CI=true

# Crea symlink para que testem encuentre "Chrome" como el binario de Chromium
RUN ln -sf /usr/bin/chromium-browser /usr/local/bin/Chrome

# Habilita pnpm via corepack (misma versión que console/Dockerfile)
RUN corepack enable

# Copia manifests del workspace primero (mejor cache de capas)
COPY pnpm-workspace.yaml package.json ./

# Copia los paquetes necesarios
COPY packages ./packages
COPY console ./console

# Instala todas las dependencias del workspace (incluyendo devDependencies)
RUN pnpm install --no-frozen-lockfile

# Instala fast-check y asegura que @ember/legacy-built-in-components esté disponible
RUN cd packages/fleetops && pnpm add --save-dev fast-check @ember/legacy-built-in-components

WORKDIR /app/packages/fleetops

# Sobreescribe testem.js para usar el launcher "chromium" disponible en Alpine
RUN node -e "\
const fs = require('fs'); \
const cfg = require('./testem.js'); \
cfg.launch_in_ci = ['chromium']; \
cfg.launch_in_dev = ['chromium']; \
cfg.browser_args = cfg.browser_args || {}; \
cfg.browser_args.chromium = { ci: ['--no-sandbox','--headless','--disable-dev-shm-usage','--disable-software-rasterizer','--mute-audio','--remote-debugging-port=0','--window-size=1440,900'] }; \
fs.writeFileSync('./testem.js', 'module.exports = ' + JSON.stringify(cfg, null, 2) + ';'); \
"

# Ejecuta solo los tests unitarios del widget (rápido, sin browser)
# ember test --filter filtra por nombre de módulo
CMD ["pnpm", "run", "test:ember", "--", "--filter", "fleet-ops-key-metrics"]
