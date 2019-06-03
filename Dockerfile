ARG PHP_VERSION=7.2
ARG NODE_VERSION=11

FROM node:${NODE_VERSION}-alpine AS nodejs

ARG PROJECT_PATH=/var/www/html/project
ARG APP_ENV=prod
WORKDIR ${PROJECT_PATH}

RUN set -eux; \
    apk add --no-cache  \
        g++ \
        gcc \
        git \
        make \
        python \
    ;

# prevent the reinstallation of vendors at every changes in the source code
COPY assets assets/
COPY package.json yarn.lock webpack.config.js ./

RUN set -eux; \
    yarn install; \
    yarn cache clean; \
    mkdir -p ${PROJECT_PATH}/public/build/; \
    NODE_ENV=prod yarn build

FROM php:${PHP_VERSION}-cli AS php

ARG APP_ENV=prod
ARG PROJECT_PATH=/var/www/html/project

RUN apt-get update && apt-get install -y --no-install-recommends \
       nano \
       procps \
       coreutils \
       libpq-dev \
       libmemcached-dev \
       libpng-dev \
       libjpeg62-turbo-dev \
       libfreetype6-dev \
       libxrender1 \
       libfontconfig \
       libxext-dev \
       apt-transport-https \
       libicu-dev \
       libxml2-dev \
       curl \
       libsodium-dev \
       librabbitmq-dev \
       libssh-dev \
       libwebp-dev \
       libzip-dev \
       libgmp-dev \
       git \
       ssh-client \
       unzip \
       acl \
       netcat \
       wget \
       supervisor \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include --with-webp-dir=/usr/include --with-freetype-dir=/usr/include/
RUN docker-php-ext-install -j$(nproc) gd

RUN docker-php-ext-configure zip --with-libzip
RUN docker-php-ext-install -j$(nproc) zip

RUN docker-php-ext-install -j$(nproc) gmp
RUN docker-php-ext-install -j$(nproc) bcmath
RUN docker-php-ext-install -j$(nproc) sockets
RUN docker-php-ext-install -j$(nproc) pdo pdo_mysql
RUN docker-php-ext-install -j$(nproc) intl
RUN docker-php-ext-install -j$(nproc) pcntl
RUN docker-php-ext-install -j$(nproc) opcache
RUN pecl install apcu && docker-php-ext-enable apcu
RUN pecl install memcached && docker-php-ext-enable memcached
RUN pecl install amqp && docker-php-ext-enable amqp
RUN pecl clear-cache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY .docker/php/php.ini /usr/local/etc/php/

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN set -eux; \
    echo "memory_limit=2G" >> /usr/local/etc/php/php-cli.ini; \
    composer global require "hirak/prestissimo:^0.3" --prefer-dist --no-progress --no-suggest --classmap-authoritative; \
    composer clear-cache
ENV PATH="${PATH}:/root/.composer/vendor/bin"
ENV APP_ENV=${APP_ENV}
ENV APP_NAME=webdollar_payment_proxy
ENV DAEMONS_USER=www-data
ENV WEBDOLLAR_CLIENT_NODE_1_URL=''
ENV WEBDOLLAR_CLIENT_NODE_1_USER=''
ENV WEBDOLLAR_CLIENT_NODE_1_PASS=''

WORKDIR ${PROJECT_PATH}

COPY composer.json composer.lock symfony.lock ./
# copy only specifically what we need
COPY bin bin/
COPY config config/
COPY public public/
COPY src src/
COPY templates templates/
COPY translations translations/
COPY .env .env

RUN set -eux; \
    mkdir -p var/cache var/log; \
    composer install --no-dev --prefer-dist --optimize-autoloader --no-progress --no-suggest; \
    composer dump-env prod; \
    composer clear-cache; \
    chmod +x bin/console; sync;

RUN bin/console cache:clear --env=${APP_ENV} --no-debug
RUN bin/console assets:install --env=${APP_ENV} --no-debug

COPY --from=nodejs ${PROJECT_PATH}/public public/

COPY .docker/php/supervisor/conf.d/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY .docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

RUN chmod +x /usr/local/bin/docker-entrypoint

RUN chown -R www-data:www-data ${PROJECT_PATH}

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
