FROM php:8.1.1-apache AS core

RUN apt-get update \
    && apt-get install -y \
        libicu-dev \
        libonig-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && mv /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load

COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/000-docker.ini
COPY ./docker/apache/apache2.conf /etc/apache2/apache2.conf
COPY ./docker/apache/ports.conf /etc/apache2/ports.conf
COPY ./docker/apache/app.conf /etc/apache2/sites-available/000-default.conf

###############################################################################

FROM core AS dev-tools

ENV COMPOSER_HOME=/tmp

RUN apt-get update \
    && apt-get install -y \
        git \
        unzip \
        curl \
        nodejs \
        npm \
    && npm install -g yarn \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY --from=composer:2.1.12 /usr/bin/composer /usr/bin/composer
COPY ./docker/composer/php.ini /usr/local/etc/php/conf.d/custom.ini

###############################################################################

FROM dev-tools AS vendors

WORKDIR /srv/app
COPY composer.json composer.lock symfony.lock ./
RUN composer install \
        --no-scripts \
        --no-interaction \
        --no-ansi \
        --prefer-dist \
        --optimize-autoloader \
        --no-dev

###############################################################################

FROM dev-tools AS frontend

WORKDIR /srv/app
COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile
RUN yarn build

###############################################################################

FROM core AS production

ARG APP_ENV=prod
ARG USER=www-data

RUN mkdir -p /srv/app && chown $USER /srv/app
WORKDIR /srv/app
USER $USER
ENV APP_ENV=$APP_ENV

COPY . .
COPY --from=vendors /srv/app/vendor vendor
COPY --from=frontend /srv/app/public/build public/build

RUN cp -n .env.dist .env \
    && php bin/console cache:warmup \
    && php bin/console assets:install public

###############################################################################

FROM dev-tools as development

ARG USER=www-data

RUN pecl install \
        xdebug \
        pcov  \
    && docker-php-ext-enable \
        xdebug \
        pcov

RUN mkdir -p /srv/app && chown $USER /srv/app
WORKDIR /srv/app
USER $USER
