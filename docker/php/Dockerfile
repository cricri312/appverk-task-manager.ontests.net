FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    nodejs \
    npm

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

RUN if [ ! -f "composer.json" ]; then \
        symfony new . --version="6.4.*" --no-git; \
        composer require webapp; \
        composer require symfony/uid; \
        composer require doctrine/doctrine-bundle; \
        composer require doctrine/orm; \
        composer require doctrine/doctrine-migrations-bundle; \
        composer require stof/doctrine-extensions-bundle; \
        composer require symfony/security-bundle; \
        composer require symfony/form; \
        composer require symfony/validator; \
        composer require --dev symfony/maker-bundle; \
        composer require --dev doctrine/doctrine-fixtures-bundle; \
    fi

RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
