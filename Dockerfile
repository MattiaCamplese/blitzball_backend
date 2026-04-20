FROM php:8.2-apache

# Estensioni PostgreSQL + unzip per Composer
RUN apt-get update && apt-get install -y libpq-dev unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean

# Installa Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Abilita mod_rewrite
RUN a2enmod rewrite

# Configura document root su public/
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Copia tutto il progetto
COPY . /var/www/html/

# Installa dipendenze PHP
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80