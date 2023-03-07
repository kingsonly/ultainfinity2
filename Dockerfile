FROM php:8.1.0
RUN apt-get update -y && apt-get install -y openssl zip unzip git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pdo_mysql
WORKDIR /var/www/html/
COPY . .
RUN composer install --optimize-autoloader --no-dev
