# console/Dockerfile - opción A
FROM node:18.15.0-alpine AS builder

WORKDIR /app

# Copiar todo el monorepo incluyendo workspace y packages
COPY . /app

# Instalar dependencias necesarias para compilar
RUN apk add --no-cache git python3 make g++
RUN npm install -g pnpm ember-cli

# Instalar dependencias desde la raíz del workspace
RUN pnpm install

# Build del console
WORKDIR /app/console
RUN pnpm run build

# Stage final con Nginx
FROM nginx:alpine
RUN rm -f /etc/nginx/conf.d/*
RUN echo 'server { listen 4200; root /usr/share/nginx/html; location / { try_files $uri $uri/ /index.html; } }' > /etc/nginx/conf.d/app.conf

COPY --from=builder /app/console/dist /usr/share/nginx/html

EXPOSE 4200
CMD ["nginx", "-g", "daemon off;"]
