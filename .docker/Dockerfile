FROM dunglas/frankenphp:latest-php8.4

WORKDIR /app

RUN install-php-extensions \
    pdo_mysql \
    gd \
    intl \
    zip \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY .docker/caddy/Caddyfile /etc/caddy/Caddyfile
