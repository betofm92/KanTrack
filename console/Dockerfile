# console/Dockerfile
FROM node:20-alpine AS builder

WORKDIR /app

# deps de build
RUN apk add --no-cache git python3 make g++

# habilita pnpm via corepack, fijando pnpm 9 (compatible con Node 20)
RUN corepack enable && corepack prepare pnpm@9 --activate

# Copia primero manifests para cache
COPY pnpm-workspace.yaml package.json ./

# Copia workspace
COPY packages ./packages
COPY console ./console

# Instala dependencias del workspace
RUN pnpm install --no-frozen-lockfile

# sanity check: deben existir los packages
RUN test -d /app/packages/dev-engine && test -d /app/packages/ember-core

# Build del console
WORKDIR /app/console
RUN pnpm run build

# nginx stage
FROM nginx:alpine
RUN rm -f /etc/nginx/conf.d/*
COPY --from=builder /app/console/dist /usr/share/nginx/html

# index.html: no cache — ensures users always get the latest app version after deploy
# /assets/*: cache forever — Ember fingerprints filenames, so new content = new URL
RUN printf 'server {\n\
    listen 4200;\n\
    root /usr/share/nginx/html;\n\
    gzip on;\n\
    gzip_types text/plain text/css application/javascript application/json;\n\
\n\
    location = /index.html {\n\
        add_header Cache-Control "no-cache, no-store, must-revalidate";\n\
        add_header Pragma "no-cache";\n\
        add_header Expires "0";\n\
    }\n\
\n\
    location /assets/ {\n\
        expires 1y;\n\
        add_header Cache-Control "public, immutable";\n\
    }\n\
\n\
    location / {\n\
        try_files $uri $uri/ /index.html;\n\
    }\n\
}\n' > /etc/nginx/conf.d/app.conf

EXPOSE 4200
CMD ["nginx", "-g", "daemon off;"]
