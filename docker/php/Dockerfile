FROM php:8.5-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update

RUN apt-get install -y --no-install-recommends git unzip libzip-dev $PHPIZE_DEPS

RUN docker-php-ext-install pdo_mysql bcmath zip

RUN pecl install redis \
    && docker-php-ext-enable redis

RUN a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/start.sh /usr/local/bin/start-php

RUN chmod +x /usr/local/bin/start-php

WORKDIR /var/www/html
