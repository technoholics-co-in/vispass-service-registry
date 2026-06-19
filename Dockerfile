FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite headers

WORKDIR /var/www/html

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf

EXPOSE 80

COPY docker/entrypoint.sh /usr/local/bin/service-registry-entrypoint.sh
RUN chmod +x /usr/local/bin/service-registry-entrypoint.sh

CMD ["/usr/local/bin/service-registry-entrypoint.sh"]
