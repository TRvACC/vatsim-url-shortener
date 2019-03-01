FROM node:10-alpine AS resources

WORKDIR /home/node/app
RUN chown node:node /home/node/app

USER node

COPY package*.json /home/node/app/

RUN npm install

COPY --chown=node:node public /home/node/app/public
COPY resources/js /home/node/app/resources/js
COPY resources/sass /home/node/app/resources/sass
COPY webpack.mix.js /home/node/app/

RUN npm run production

########################################

FROM nginxinc/nginx-unprivileged:1.14-alpine AS nginx

USER root

RUN apk add --update --no-cache \
        curl

COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/server.conf /etc/nginx/conf.d/default.conf

COPY --from=resources /home/node/app/public /var/www/html/public

USER nginx

HEALTHCHECK --start-period=15s --interval=30s --timeout=5s \
    CMD curl -f http://localhost:8081/health || exit 1

EXPOSE 8080 8081

########################################

FROM php:7.3-fpm-alpine AS php-fpm

RUN apk add --update --no-cache --virtual build-dependencies \
        autoconf gcc g++ libtool make \
    && apk add --update --no-cache \
        libmcrypt-dev \
        mysql-client \
        libpng-dev \
        unzip \
        fcgi \
    && pecl install mcrypt-1.0.2 \
    && docker-php-ext-enable mcrypt \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install gd \
    && apk del build-dependencies

WORKDIR /var/www/html

USER www-data

ARG COMPOSER_VERSION=1.8.4
RUN EXPECTED_SIGNATURE="$(curl -s https://composer.github.io/installer.sig)"; \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
    ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"; \
    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then \
        >&2 echo 'ERROR: Invalid installer signature'; \
        rm composer-setup.php; \
        exit 1; \
    fi; \
    php composer-setup.php --quiet --version $COMPOSER_VERSION; \
    RESULT=$?; \
    rm composer-setup.php; \
    exit $RESULT

COPY composer.* /var/www/html/

RUN php composer.phar docker-install

COPY . /var/www/html
COPY --from=resources /home/node/app/public /var/www/html/public

USER root
RUN chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache
USER www-data

RUN php composer.phar docker-build

USER root
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
        && echo 'log_errors_max_len = 0' >> $PHP_INI_DIR/conf.d/app.ini \
        && echo 'cgi.fix_pathinfo = 0' >> $PHP_INI_DIR/conf.d/app.ini \
        && echo 'date.timezone = UTC' >> $PHP_INI_DIR/conf.d/app.ini
COPY ./docker/app-fpm.conf /usr/local/etc/php-fpm.d/app-fpm.conf
USER www-data

ARG APP_COMMIT
ENV APP_COMMIT $APP_COMMIT

ARG APP_VERSION
ENV APP_VERSION $APP_VERSION

HEALTHCHECK --start-period=15s --interval=30s --timeout=5s \
    CMD \
        SCRIPT_NAME=/ping \
        SCRIPT_FILENAME=/ping \
        REQUEST_METHOD=GET \
        cgi-fcgi -bind -connect 127.0.0.1:9000 | tee /dev/stderr | grep pong || exit 1

COPY ./docker/php-fpm-entrypoint.sh /var/www/html/entrypoint.sh
ENTRYPOINT ["/var/www/html/entrypoint.sh"]
CMD ["php-fpm"]
