# console/Dockerfile
FROM node:20-alpine AS builder

WORKDIR /app

# deps de build
RUN apk add --no-cache git python3 make g++

# habilita pnpm via corepack
RUN corepack enable

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
RUN echo 'server { listen 4200; root /usr/share/nginx/html; location / { try_files $uri $uri/ /index.html; } }' > /etc/nginx/conf.d/app.conf

COPY --from=builder /app/console/dist /usr/share/nginx/html

EXPOSE 4200
CMD ["nginx", "-g", "daemon off;"]
