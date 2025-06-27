FROM php:8.3-fpm-alpine AS php-base

WORKDIR /build

# PHP Extensions installieren
RUN apk add --no-cache \
    git unzip curl libpng-dev libxml2-dev libzip-dev oniguruma-dev icu-dev autoconf g++ make \
    && docker-php-ext-install intl bcmath zip pdo pdo_mysql

# Composer installieren
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Abhängigkeiten installieren
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-autoloader --no-scripts

FROM node:20-alpine AS yarn
WORKDIR /build
COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile

FROM php-base AS composerbuild
COPY . .
RUN composer dump-autoload --optimize

FROM yarn AS yarnbuild
WORKDIR /build
COPY . .
COPY --from=php-base /build .
RUN yarn run build

FROM php:8.3-fpm-alpine AS final
WORKDIR /var/www/html

# Wieder PHP Extensions im finalen Image
RUN apk add --no-cache \
    caddy ca-certificates supervisor supercronic curl icu-dev \
    && docker-php-ext-install intl bcmath zip pdo pdo_mysql

COPY --from=composerbuild /build .
COPY --from=yarnbuild /build/public ./public

COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/crontab /etc/supercronic/crontab
COPY docker/entrypoint.sh ./docker/entrypoint.sh

EXPOSE 80 443
VOLUME /turbopanel-data

USER www-data

ENTRYPOINT [ "/bin/ash", "docker/entrypoint.sh" ]
CMD [ "supervisord", "-n", "-c", "/etc/supervisord.conf" ]
