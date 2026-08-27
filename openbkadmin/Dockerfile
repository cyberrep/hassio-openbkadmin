ARG BUILD_FROM=ghcr.io/hassio-addons/base:19.0.0
FROM ${BUILD_FROM}
SHELL ["/bin/bash", "-o", "pipefail", "-c"]
RUN apk add --no-cache \
        nginx php83 php83-curl php83-dom php83-fpm php83-iconv \
        php83-mbstring php83-opcache php83-openssl php83-session php83-zip php83-phar \
    && apk add --no-cache --virtual .build-dependencies composer nodejs npm
COPY app /var/www/openbkadmin
WORKDIR /var/www/openbkadmin
RUN rm -rf /var/www/openbkadmin/tmp/cache/i18n/*
RUN rm -rf vendor \
    && composer install --no-dev --optimize-autoloader \
    && npm ci \
    && npm run build \
    && test -s resources/js/compiled/vendor.js \
    && test -s resources/js/compiled/OpenBeken.js \
    && test -s resources/js/compiled/app.js \
    && test -s resources/css/compiled/all.css \
    && php -r 'if (!class_exists("PharData")) { fwrite(STDERR, "PharData unavailable\n"); exit(1); }' \
    && rm -rf node_modules \
    && apk del --no-cache --purge .build-dependencies \
    && rm -rf /root/.composer /var/www/openbkadmin/tests \
    && find /var/www/openbkadmin -type f -name ".htaccess" -depth -exec rm -f {} \;
COPY rootfs /
HEALTHCHECK CMD if ! curl --fail http://127.0.0.1:9542 && ! curl --fail --insecure https://127.0.0.1:9542; then exit 1; fi
