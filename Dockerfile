# ============================================================
# Dockerfile for Railway
# Builds vendor/ fresh via Composer, then serves the app with
# Apache + PHP 8.2, listening on Railway's dynamic $PORT.
# ============================================================

# ---- Stage 1: install PHP dependencies -------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --ignore-platform-reqs

# ---- Stage 2: runtime image --------------------------------
FROM php:8.2-apache

# PDO MySQL is what db.php actually uses; mysqli/gd/mbstring
# cover PHPMailer + dompdf's needs.
#
# Note: mod_php requires the prefork MPM. Installing libzip-dev/
# libpng-dev below can pull in and auto-enable mpm_event as a
# dependency, which then conflicts with prefork at startup
# ("AH00534: More than one MPM loaded"). The last two lines
# force prefork back on and disable the others.
RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libpng-dev \
    && docker-php-ext-install gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/* \
    && (a2dismod mpm_event mpm_worker || true) \
    && a2enmod mpm_prefork

WORKDIR /var/www/html
COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor

# setup-org.php writes uploaded logos here — make sure it
# exists and is writable (note: this is NOT persistent
# storage on Railway; see the deployment notes).
RUN mkdir -p /var/www/html/assets/uploads \
    && chown -R www-data:www-data /var/www/html

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
