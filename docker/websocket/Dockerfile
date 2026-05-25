FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_mysql sockets \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

COPY composer.json .
COPY composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

EXPOSE 8090

CMD ["sh", "-c", "composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null; php server.php"]
