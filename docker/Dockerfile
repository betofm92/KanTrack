# syntax = docker/dockerfile:1.2
FROM dunglas/frankenphp:1.5.0-php8.2-bookworm AS base

# Install packages
RUN apt-get update && apt-get install -y git bind9-utils mycli nodejs npm nano uuid-runtime \
  && mkdir -p /root/.ssh \
  && ssh-keyscan github.com >> /root/.ssh/known_hosts

# Install PHP Extensions
RUN install-php-extensions \
  pdo_mysql \
  gd \
  bcmath \
  redis \
  intl \
  zip \
  gmp \
  apcu \
  opcache \
  memcached \
  sockets \
  pcntl \
  @composer

# Install build dependencies for GEOS
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       pkg-config \
       libgeos-dev \
       libgeos++-dev \
       autoconf \
       build-essential \
       unzip \
    && rm -rf /var/lib/apt/lists/*

# Download, extract, compile, and enable the php-geos extension
RUN curl -fsSL -o php-geos.zip \
      https://github.com/libgeos/php-geos/archive/dfe1ab17b0f155cc315bc13c75689371676e02e1.zip \
    && unzip php-geos.zip \
    && rm php-geos.zip \
    && cd php-geos-* \
    && ./autogen.sh \
    && ./configure \
    && make -j"$(nproc)" install \
    && docker-php-ext-enable geos \
    && cd .. \
    && rm -rf php-geos-*

# Update PHP configurations
RUN sed -e 's/^expose_php.*/expose_php = Off/' "$PHP_INI_DIR/php.ini-production" > "$PHP_INI_DIR/php.ini" \
  && sed -i -e 's/^upload_max_filesize.*/upload_max_filesize = 50M/' -e 's/^post_max_size.*/post_max_size = 50M/' \
  -e 's/^memory_limit.*/memory_limit = 256M/' "$PHP_INI_DIR/php.ini" \
  && echo 'opcache.memory_consumption=64' >> "$PHP_INI_DIR/php.ini" \
  && echo 'opcache.interned_strings_buffer=8' >> "$PHP_INI_DIR/php.ini" \
  && echo 'opcache.max_accelerated_files=4000' >> "$PHP_INI_DIR/php.ini" \
  && echo 'opcache.revalidate_freq=60' >> "$PHP_INI_DIR/php.ini" \
  && echo 'opcache.fast_shutdown=1' >> "$PHP_INI_DIR/php.ini"

# Install global node modules
RUN npm install -g chokidar pnpm ember-cli npm-cli-login

# Create the pnpm directory and set the PNPM_HOME environment variable
RUN mkdir -p ~/.pnpm
ENV PNPM_HOME=/root/.pnpm
ENV PATH=/root/.pnpm/bin:$PATH

# Set some build ENV variables
ENV LOG_CHANNEL=stdout
ENV CACHE_DRIVER=null
ENV BROADCAST_DRIVER=socketcluster
ENV QUEUE_CONNECTION=redis
ENV CADDYFILE_PATH=/fleetbase/Caddyfile
ENV CONSOLE_PATH=/fleetbase/console
ENV OCTANE_SERVER=frankenphp
ENV FLEETBASE_VERSION=0.7.19

ARG ENVIRONMENT=production
ENV APP_ENV=$ENVIRONMENT

ARG GITHUB_AUTH_KEY

COPY --chown=www-data:www-data ./Caddyfile $CADDYFILE_PATH

RUN mkdir -p /fleetbase/api && mkdir -p /fleetbase/console && chown -R www-data:www-data /fleetbase
RUN uuidgen > /fleetbase/api/.fleetbase-id
RUN export FLEETBASE_INSTANCE_ID=$(cat /fleetbase/api/.fleetbase-id) && \
    echo "FLEETBASE_INSTANCE_ID=$FLEETBASE_INSTANCE_ID" >> /etc/environment

WORKDIR /fleetbase/api

RUN if [ -n "$GITHUB_AUTH_KEY" ]; then echo "{\"github-oauth\": {\"github.com\": \"$GITHUB_AUTH_KEY\"}}" > auth.json; fi
RUN mkdir -p /var/www/.cache/composer && chown -R www-data:www-data /var/www/.cache/composer

# Copy local packages so composer can install them as path repos
COPY --chown=www-data:www-data ./packages /fleetbase/packages
COPY --chown=www-data:www-data ./api /fleetbase/api

# Inject path repositories into composer.json so local packages take priority over the registry
RUN su www-data -s /bin/sh -c "php -r '\$cfg = json_decode(file_get_contents(\"composer.json\"), true); array_unshift(\$cfg[\"repositories\"], [\"type\" => \"path\", \"url\" => \"../../packages/*\", \"options\" => [\"symlink\" => false]]); file_put_contents(\"composer.json\", json_encode(\$cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));'"

RUN su www-data -s /bin/sh -c "composer install --no-scripts --optimize-autoloader --no-dev --classmap-authoritative"

# Overwrite vendor packages with local source (picks up all custom changes automatically)
COPY --chown=www-data:www-data ./packages/core-api/src /fleetbase/api/vendor/fleetbase/core-api/src
COPY --chown=www-data:www-data ./packages/core-api/migrations /fleetbase/api/vendor/fleetbase/core-api/migrations
COPY --chown=www-data:www-data ./packages/fleetops/server/src /fleetbase/api/vendor/fleetbase/fleetops-api/server/src

RUN su www-data -s /bin/sh -c "composer dumpautoload --optimize"
RUN test -f /fleetbase/api/vendor/autoload.php && echo "✓ autoload.php creado correctamente" || (echo "✗ ERROR: autoload.php no encontrado" && ls -la /fleetbase/api/vendor/ && exit 1)

RUN mkdir -p /root/.composer
RUN mkdir -p /fleetbase/api/.composer && chown www-data:www-data /fleetbase/api/.composer

RUN mkdir -p /fleetbase/api/storage/logs/ && touch /fleetbase/api/storage/logs/laravel-$(date +'%Y-%m-%d').log
RUN mkdir -p /fleetbase/api/storage/fonts/
RUN chown -R www-data:www-data /fleetbase/api/storage
RUN chmod -R 775 /fleetbase/api/storage

RUN chmod +x /fleetbase/api/deploy.sh

RUN curl -L https://github.com/webdevops/go-crond/releases/download/23.12.0/go-crond.linux.amd64 > /usr/local/bin/go-crond && chmod +x /usr/local/bin/go-crond
COPY docker/crontab ./crontab
RUN chmod 0600 ./crontab

# Scheduler stage
FROM base AS scheduler
ENTRYPOINT []
CMD ["go-crond", "--verbose", "root:./crontab"]

# Events stage
FROM base AS events
ENTRYPOINT ["docker-php-entrypoint"]
CMD ["php", "artisan", "queue:work --max-jobs=50 --max-memory=64"]

# Application stage
FROM base AS app
ENV APP_DEBUG=false
ENV OCTANE_WORKERS=4
ENTRYPOINT ["docker-php-entrypoint"]
CMD ["sh", "-c", "php artisan octane:frankenphp --workers=${OCTANE_WORKERS} --max-requests=250 --port=8000 --host=0.0.0.0"]
