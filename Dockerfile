FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libpq-dev libonig-dev libxml2-dev libsqlite3-dev \
    && docker-php-ext-install pdo_pgsql pdo_sqlite mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN touch .env
RUN git config --global --add safe.directory /var/www/html
ARG COMPOSER_NO_DEV=0
RUN if [ "$COMPOSER_NO_DEV" = "1" ]; then composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader; else composer install --prefer-dist --no-interaction --optimize-autoloader; fi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' >> /etc/apache2/apache2.conf
EXPOSE 80
