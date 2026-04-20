FROM php:8.2-apache

# Estensioni necessarie per PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean

# Copia il codice
COPY . /var/www/html/

# Permessi
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80