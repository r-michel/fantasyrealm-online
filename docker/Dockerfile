FROM php:8.3-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install \
        intl \
        pdo \
        pdo_mysql \
        opcache \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Aligne www-data sur l'utilisateur de la machine hôte
RUN groupmod --gid "${GID}" www-data \
    && usermod --uid "${UID}" --gid "${GID}" www-data

WORKDIR /var/www

CMD ["php-fpm"]