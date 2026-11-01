FROM php:8.3-apache

# Extensiones: pdo_mysql (BD), gd + exif (re-codificar imágenes subidas), gmp (web-push), opcache
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libzip-dev \
    libgmp-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        gd \
        exif \
        zip \
        gmp \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# mod_rewrite (URLs limpias) y mod_headers (cabeceras de seguridad del .htaccess)
RUN a2enmod rewrite headers

# VirtualHost: DocumentRoot en public/ y PHP desactivado en la carpeta de subidas
RUN printf '%s\n' \
    '<VirtualHost *:80>' \
    '    DocumentRoot /var/www/html/public' \
    '    <Directory /var/www/html/public>' \
    '        AllowOverride All' \
    '        Require all granted' \
    '    </Directory>' \
    '    <Directory /var/www/html/public/uploads>' \
    '        php_admin_flag engine off' \
    '        <FilesMatch "(?i)\.(php[0-9]?|phtml|phar|pht|phps)$">' \
    '            Require all denied' \
    '        </FilesMatch>' \
    '    </Directory>' \
    '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
    '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf \
    && printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/zz-hardening.conf \
    && a2enconf zz-hardening

# Configuración de PHP
RUN printf '%s\n' \
    'upload_max_filesize = 6M' \
    'post_max_size = 8M' \
    'max_execution_time = 60' \
    'display_errors = Off' \
    'log_errors = On' \
    'expose_php = Off' \
    > /usr/local/etc/php/conf.d/custom.ini

# Dependencias PHP (web-push) fuera de la carpeta montada como volumen:
# así existen también en un clon limpio, donde app/vendor/ no está.
WORKDIR /opt/app-deps
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

WORKDIR /var/www/html

EXPOSE 80
