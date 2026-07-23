FROM dunglas/frankenphp:php8.5

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY .docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY .docker/caddy/Caddyfile /etc/frankenphp/Caddyfile
COPY .docker/caddy/worker.production.caddyfile /etc/frankenphp/worker.environment.caddyfile
