# Create image from php and apache image
FROM php:8-apache

# Setup SSL (https, coming soon)
#RUN a2enmod ssl && a2enmod rewrite
#RUN mkdir -p /etc/apache2/ssl

# Changes php initialization from development to production (i think)
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Copy the site default config files into sites available on container
COPY ./000-default.conf /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html

RUN docker-php-ext-install mysqli


# Expose http and https ports
# http
EXPOSE 80
# https
EXPOSE 443