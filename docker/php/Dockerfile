FROM php:8.5-apache

ENV APACHE_DOCUMENT_ROOT=/srv/public

COPY docker/php/start.sh /usr/local/bin/start-php

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql bcmath zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/start-php \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /srv
