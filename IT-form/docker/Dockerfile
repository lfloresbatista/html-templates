# IT-form web image — non-root, código embebido, listo para proxy
# Build (desde raíz IT-form/):
#   docker build -f docker/Dockerfile -t it-form:local .
FROM php:8.2-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql gd zip \
    && a2enmod rewrite headers remoteip \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html \
    APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data

WORKDIR /var/www/html

# Apache non-root: puerto 8080
RUN sed -ri 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf \
    && sed -ri 's/:80>/:8080>/g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# RemoteIP para nginx-proxy / Traefik / Cloudflare (X-Forwarded-For)
RUN { \
      echo 'RemoteIPHeader X-Forwarded-For'; \
      echo 'RemoteIPInternalProxy 10.0.0.0/8'; \
      echo 'RemoteIPInternalProxy 172.16.0.0/12'; \
      echo 'RemoteIPInternalProxy 192.168.0.0/16'; \
      echo 'RemoteIPInternalProxy 127.0.0.0/8'; \
      echo 'RemoteIPInternalProxy fc00::/7'; \
    } > /etc/apache2/conf-available/remoteip.conf \
    && a2enconf remoteip

RUN { \
      echo 'display_errors=0'; \
      echo 'expose_php=0'; \
      echo 'session.cookie_httponly=1'; \
      echo 'session.use_strict_mode=1'; \
      echo 'upload_max_filesize=4M'; \
      echo 'post_max_size=8M'; \
    } > /usr/local/etc/php/conf.d/itform.ini

# Copiar solo lo necesario de la app (context = IT-form/)
COPY index.php index.html login.html script.js styles.css print_pdf.php procesar_login.php router.php ./
COPY .htaccess .env.example ./
COPY admin/ ./admin/
COPY api/ ./api/
COPY config/ ./config/
COPY database/ ./database/
COPY dist/ ./dist/
COPY tcpdf/ ./tcpdf/
COPY storage/ ./storage/
COPY uploads/ ./uploads/
COPY logo.png logo2.png logo_pdf.jpg logo2_pdf.jpg ./
COPY docker/docker-entrypoint.sh /usr/local/bin/itform-entrypoint.sh

RUN chmod +x /usr/local/bin/itform-entrypoint.sh \
    && mkdir -p storage/db storage/logs uploads \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod 755 /usr/local/bin/itform-entrypoint.sh \
    && chown root:root /usr/local/bin/itform-entrypoint.sh \
    && chmod 755 /var/run/apache2 \
    && mkdir -p /var/log/apache2 /var/lock/apache2 \
    && chown -R www-data:www-data /var/log/apache2 /var/lock/apache2 /var/run/apache2

USER www-data
EXPOSE 8080
ENTRYPOINT ["itform-entrypoint.sh"]
CMD ["apache2-foreground"]
