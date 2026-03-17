FROM php:8.1-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Install extensions (PDO MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Set Apache root to /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
WORKDIR /var/www/html

COPY . .