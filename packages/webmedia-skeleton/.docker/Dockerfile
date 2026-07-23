FROM dunglas/frankenphp:php8.5

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends libvips-tools \
    && rm -rf /var/lib/apt/lists/*

# RoadRunner worker runtime requires this extension when it is enabled later:
# RUN install-php-extensions sockets
RUN install-php-extensions \
    pdo_pgsql \
    gd \
    intl \
    zip \
    opcache

RUN { \
    echo 'upload_max_filesize=512M'; \
    echo 'post_max_size=540M'; \
    echo 'display_errors=Off'; \
    echo 'display_startup_errors=Off'; \
    echo 'log_errors=On'; \
} > /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY .docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY .docker/caddy/Caddyfile /etc/frankenphp/Caddyfile
COPY .docker/caddy/worker.production.caddyfile /etc/frankenphp/worker.environment.caddyfile
