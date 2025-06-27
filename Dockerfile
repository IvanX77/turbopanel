# syntax=docker.io/docker/dockerfile:1.13-labs
# TurboPanel Production Dockerfile

# ================================
# Stage 1-1: Composer Install
# ================================
FROM php:8.3-fpm-alpine AS composer

WORKDIR /build

RUN apk add --no-cache git unzip curl libpng-dev libxml2-dev libzip-dev oniguruma-dev autoconf g++ make

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --no-autoloader --no-scripts

# ================================
# Stage 1-2: Yarn Install
# ================================
FROM node:20-alpine AS yarn

WORKDIR /build
COPY package.json yarn.lock ./
RUN yarn config set network-timeout 300000 \
  && yarn install --frozen-lockfile

# ================================
# Stage 2-1: Composer Optimize
# ================================
FROM composer AS composerbuild
COPY --exclude=Caddyfile --exclude=docker/ . ./
RUN composer dump-autoload --optimize

# ================================
# Stage 2-2: Build Frontend Assets
# ================================
FROM yarn AS yarnbuild

WORKDIR /build
COPY --exclude=Caddyfile --exclude=docker/ . ./
COPY --from=composer /build .
RUN yarn run build

# ================================
# Final Production Image
# ================================
FROM php:8.3-fpm-alpine AS final

WORKDIR /var/www/html

RUN apk add --no-cache caddy ca-certificates supervisor supercronic curl bash

# Kopiere den Build von vorherigen Stages
COPY --chown=root:www-data --chmod=640 --from=composerbuild /build .
COPY --chown=root:www-data --chmod=640 --from=yarnbuild /build/public ./public

# Symlinks und Rechte setzen
RUN chown root:www-data ./ \
    && chmod 750 ./ \
    && find ./ -type d -exec chmod 750 {} \; \
    && mkdir -p /turbopanel-data/storage /var/www/html/storage/app/public /var/run/supervisord /etc/supercronic \
    && ln -s /turbopanel-data/.env ./.env \
    && ln -s /turbopanel-data/database/database.sqlite ./database/database.sqlite \
    && ln -sf /var/www/html/storage/app/public /var/www/html/public/storage \
    && ln -s /turbopanel-data/storage/avatars /var/www/html/storage/app/public/avatars \
    && ln -s /turbopanel-data/storage/fonts /var/www/html/storage/app/public/fonts \
    && chown -R www-data:www-data /turbopanel-data ./storage ./bootstrap/cache /var/run/supervisord /var/www/html/public/storage \
    && chmod -R u+rwX,g+rwX,o-rwx /turbopanel-data ./storage ./bootstrap/cache /var/run/supervisord

# Konfigurationen kopieren
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/crontab /etc/supercronic/crontab
COPY docker/entrypoint.sh ./docker/entrypoint.sh

HEALTHCHECK --interval=5m --timeout=10s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/up || exit 1

EXPOSE 80 443

VOLUME /turbopanel-data

USER www-data

ENTRYPOINT [ "/bin/ash", "docker/entrypoint.sh" ]
CMD [ "supervisord", "-n", "-c", "/etc/supervisord.conf" ]
