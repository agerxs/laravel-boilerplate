# Étape 1 : PHP Composer
FROM composer:2 AS vendor
WORKDIR /app
# Installer dépendances système sur Alpine
RUN apk add --no-cache \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    freetype-dev \
    libzip-dev \
    unzip \
    git \
    bash \
    oniguruma-dev

# Installer extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) intl bcmath gd exif zip \
 && docker-php-ext-enable exif

COPY . .

# RUN composer install --no-dev --optimize-autoloader
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist

# Étape 2 : Node build
# ARG NODE_VERSION=22
# FROM node:${NODE_VERSION}-slim AS node
FROM node:22-slim AS node
WORKDIR /app
COPY package*.json ./
RUN npm install

COPY . .
COPY --from=vendor /app/vendor ./vendor

RUN npm run build

# Use Alpine Linux as the base for its lightweight, secure, and optimized nature for containers.
# Alpine minimizes attack surface with fewer packages and uses musl libc for smaller footprint.
FROM alpine:3.20

# Define environment variables with sensible defaults for flexibility
ENV PHP_INI_DIR=/etc/php83 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    LARAVEL_PATH=/var/www/html \
    PHP_MEMORY_LIMIT=256M \
    PHP_UPLOAD_MAX_FILESIZE=64M \
    PHP_POST_MAX_SIZE=64M \
    PHP_MAX_CHILDREN=50 \
    PHP_START_SERVERS=10 \
    PHP_MIN_SPARE_SERVERS=5 \
    PHP_MAX_SPARE_SERVERS=35 \
    PHP_MAX_REQUESTS=500 \
    GZIP_COMP_LEVEL=6 \
    NGINX_WORKER_PROCESSES=auto \
    NGINX_CLIENT_MAX_BODY_SIZE=64m

# Set working directory
WORKDIR ${LARAVEL_PATH}

# Install necessary packages in a single layer to reduce image size
# - nginx: Web server
# - php83 and extensions: For PHP-FPM, including common Laravel requirements
# - composer: Dependency manager
# - supervisor: To manage multiple processes (nginx, php-fpm, cron)
# - cronie: For cron jobs (Laravel scheduler)
# - shadow: For user management
# - Other utils: For security and optimizations
RUN apk update && apk upgrade --no-cache && \
    apk add --no-cache \
        nginx \
        curl \
        bash \
        php83 \
        php83-fpm \
        php83-openssl \
        php83-pdo \
        php83-pdo_mysql \
        php83-mbstring \
        php83-tokenizer \
        php83-xml \
        php83-ctype \
        php83-session \
        php83-json \
        php83-curl \
        php83-dom \
        php83-fileinfo \
        php83-bcmath \
        php83-gd \
        php83-iconv \
        php83-intl \
        php83-phar \
        php83-simplexml \
        php83-soap \
        php83-xmlreader \
        php83-xmlwriter \
        php83-zip \
        php83-pecl-redis \
        php83-posix \
        php83-pcntl \
        php83-exif \
        mariadb-client \
        gzip \
        supervisor \
        cronie \
        gettext \
        shadow

# Ajout utilisateur sécurisé
RUN getent group www-data || addgroup -g 82 -S www-data && getent passwd www-data || adduser -u 82 -S -G www-data -s /sbin/nologin www-data

COPY --chown=www-data:www-data  . .
COPY --from=node /app/public /var/www/html/public
COPY --from=node /app/node_modules /var/www/html/node_modules
COPY --from=vendor /app/vendor /var/www/html/vendor

# Install Composer and dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist

# Set up directories with proper permissions
RUN mkdir -p ${LARAVEL_PATH} /run/nginx /run/php /var/log/supervisor /var/log/nginx /var/log/php83 \
    /var/lib/nginx/tmp/client_body /var/lib/nginx/tmp/fastcgi /var/lib/nginx/tmp/uwsgi \
    /var/lib/nginx/tmp/proxy /var/lib/nginx/tmp/scgi

RUN chown -R www-data:www-data /run/nginx /run/php /var/log/supervisor /var/log/nginx /var/log/php83 /var/lib/nginx
RUN chmod -R 750 /run/nginx /run/php /var/log/supervisor /var/log/nginx /var/log/php83 /var/lib/nginx

# Clean up to reduce image size
RUN rm -rf /var/cache/apk/* /tmp/*

# Configure PHP-FPM for security and performance
# - Run as non-root user
# - Optimize pool settings for Laravel using environment variables
# - Enable opcache for PHP performance
RUN echo "memory_limit = ${PHP_MEMORY_LIMIT}" >> ${PHP_INI_DIR}/conf.d/custom.ini && \
    echo "upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}" >> ${PHP_INI_DIR}/conf.d/custom.ini && \
    echo "post_max_size = ${PHP_POST_MAX_SIZE}" >> ${PHP_INI_DIR}/conf.d/custom.ini && \
    echo "opcache.enable=1" >> ${PHP_INI_DIR}/conf.d/custom.ini && \
    echo "opcache.memory_consumption=128" >> ${PHP_INI_DIR}/conf.d/custom.ini && \
    echo "opcache.interned_strings_buffer=8" >> ${PHP_INI_DIR}/conf.d/custom.ini && \
    echo "opcache.max_accelerated_files=10000" >> ${PHP_INI_DIR}/conf.d/custom.ini && \
    echo "opcache.revalidate_freq=2" >> ${PHP_INI_DIR}/conf.d/custom.ini && \
    echo "opcache.fast_shutdown=1" >> ${PHP_INI_DIR}/conf.d/custom.ini

# Configure NGINX for security and performance
# - External config file for maintainability
# - Apply worker_processes and client_max_body_size via env variables
COPY ./docker/nginx-colocs.conf /etc/nginx/http.d/default.conf
COPY ./docker/www.conf /etc/php83/php-fpm.d/www.conf

RUN sed -i "s/user nginx;/user www-data;/g" /etc/nginx/nginx.conf && \
    sed -i "s/worker_processes  1;/worker_processes ${NGINX_WORKER_PROCESSES};/g" /etc/nginx/nginx.conf && \
    sed -i "s/# server_tokens off;/server_tokens off;/g" /etc/nginx/nginx.conf && \
    sed -i "s/# client_max_body_size 1m;/client_max_body_size ${NGINX_CLIENT_MAX_BODY_SIZE};/g" /etc/nginx/nginx.conf && \
    sed -i "s|error_log /var/lib/nginx/logs/error.log;|error_log /var/log/nginx/error.log;|g" /etc/nginx/nginx.conf && \
    # Test NGINX config
    nginx -t

# Configure cron for Laravel scheduler
# - Run every minute as non-root user
# - Log to a dedicated file for debugging
RUN echo "* * * * * su -s /bin/sh www-data -c 'php ${LARAVEL_PATH}/artisan schedule:run >> /var/log/cron-laravel.log 2>&1'" > /etc/crontabs/www-data && \
    crontab -u www-data /etc/crontabs/www-data && \
    touch /var/log/cron-laravel.log && \
    chown www-data:www-data /var/log/cron-laravel.log && \
    chmod 640 /var/log/cron-laravel.log

# Configure Supervisor to manage processes
# - External config for maintainability
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Copy the entrypoint script
COPY ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY ./docker/init-app.sh /usr/local/bin/init-app.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/init-app.sh

# Healthcheck to ensure services are running
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD pgrep nginx && pgrep php-fpm83 && pgrep crond || exit 1

# Declare volume for Laravel app (persistent storage, mounted at runtime)
VOLUME [${LARAVEL_PATH}]

# Expose port
EXPOSE 80

# USER www-data

# Change the default command to run the entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Use Supervisor as entrypoint to run all services
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]