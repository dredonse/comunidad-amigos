FROM php:8.3-apache

# Instalar extensiones necesarias (gmp requerido por web-push)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libzip-dev \
    libgmp-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        gd \
        zip \
        gmp \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Habilitar mod_rewrite para URLs limpias
RUN a2enmod rewrite

# VirtualHost: DocumentRoot en public/ (index.php del router)
RUN printf '%s\n' \
    '<VirtualHost *:80>' \
    '    DocumentRoot /var/www/html/public' \
    '    <Directory /var/www/html/public>' \
    '        AllowOverride All' \
    '        Require all granted' \
    '    </Directory>' \
    '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
    '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf

# Configuración de PHP
RUN echo "upload_max_filesize = 10M\n\
post_max_size = 10M\n\
max_execution_time = 60\n\
display_errors = Off\n\
log_errors = On" > /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

# Dependencias PHP (web-push); el volumen monta app/ en runtime
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null \
    || composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 80
