FROM php:8.2-apache

# Estensioni PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean

# Abilita mod_rewrite (necessario per pecee/simple-router)
RUN a2enmod rewrite

# Configura Apache per usare public/ come document root
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Permetti .htaccess con AllowOverride
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Copia tutto il progetto
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80