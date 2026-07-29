# IT-form — PHP 8.2 Apache + pdo_mysql + gd
FROM php:8.2-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql gd zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html
WORKDIR /var/www/html

# Allow .htaccess
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# PHP production-ish defaults
RUN { \
      echo 'display_errors=0'; \
      echo 'expose_php=0'; \
      echo 'session.cookie_httponly=1'; \
      echo 'session.use_strict_mode=1'; \
      echo 'upload_max_filesize=4M'; \
      echo 'post_max_size=8M'; \
    } > /usr/local/etc/php/conf.d/itform.ini

COPY docker/docker-entrypoint.sh /usr/local/bin/itform-entrypoint.sh
RUN chmod +x /usr/local/bin/itform-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["itform-entrypoint.sh"]
CMD ["apache2-foreground"]
