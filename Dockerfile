FROM php:8.3.7-apache
WORKDIR /var/www

RUN apt-get update && apt-get -y install build-essential \ 
                                         libsqlite3-dev \
                                         libicu-dev \
                                         unzip \
                                         git 

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN a2enmod rewrite

RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_sqlite
RUN docker-php-ext-install intl
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install pdo_mysql

# Instalando o PHP
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

COPY ./ /var/www/