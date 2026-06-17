# syntax=docker/dockerfile:1
# Single dev image used by the app (php-fpm), queue worker and scheduler.
# 8.4 matches the Herd toolchain the lockfile is resolved against (app requires ^8.3).
FROM php:8.4-fpm

# System dependencies + tools.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        zip \
        unzip \
        procps \
        libonig-dev \
        libzip-dev \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required by the app (pdo_mysql, queue signals via pcntl, etc.).
RUN docker-php-ext-install pdo_mysql mbstring bcmath pcntl zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Node.js 20 for building front-end assets.
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/local.ini /usr/local/etc/php/conf.d/zz-local.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
