FROM amazonlinux:2023

ARG PHP_BUILD_DIR=/var/task
ARG PHP_CONF_DIR=/etc/php.d
ARG PHP_EXT_DIR=/usr/local/php/lib/php/extensions/no-debug-non-zts-20240924
ARG PHP_VERSION=8.4.1

RUN yum install -y \
    gcc \
    make \
    autoconf \
    bison \
    re2c \
    libxml2-devel \
    libzip-devel \
    oniguruma-devel \
    curl-devel \
    libpng-devel \
    libjpeg-devel \
    freetype-devel \
    openssl-devel \
    sqlite-devel \
    bzip2-devel \
    libcurl-devel \
    libicu-devel \
    libxslt-devel \
    libffi-devel \
    systemd-devel \
    git \
    tar \
    wget \
    && yum clean all

RUN wget https://www.php.net/distributions/php-${PHP_VERSION}.tar.gz \
    && tar -xzf php-${PHP_VERSION}.tar.gz -C /usr/local/src \
    && rm php-${PHP_VERSION}.tar.gz

RUN cd /usr/local/src/php-${PHP_VERSION} \
    && ./configure \
        --prefix=/usr/local/php \
        --with-config-file-path=/usr/local/php/etc \
        --with-config-file-scan-dir=/usr/local/php/etc/conf.d \
        --enable-mbstring \
        --with-curl \
        --with-openssl \
        --with-zlib \
        --enable-bcmath \
        --enable-mbregex \
        --enable-pcntl \
        --enable-sockets \
        --with-mysqli \
        --with-pdo-mysql \
        --with-pdo-sqlite \
        --with-zip \
        --with-gd \
        --with-jpeg \
        --with-freetype \
        --enable-opcache \
        --enable-fpm \
    && make -j"$(nproc)" \
    && make install

RUN ln -s /usr/local/php/bin/php /usr/bin/php \
    && ln -s /usr/local/php/bin/phpize /usr/bin/phpize \
    && ln -s /usr/local/php/bin/php-config /usr/bin/php-config

#Extension install
RUN mkdir -p ${PHP_EXT_DIR} && mkdir -p ${PHP_CONF_DIR}

#shadow
RUN mkdir -p ${PHP_BUILD_DIR}
RUN cd ${PHP_BUILD_DIR} && \
    mkdir shadow
COPY shadow.c shadow/
COPY php_shadow.h shadow/
COPY shadow_cache.c shadow/
COPY shadow_cache.h shadow/
COPY config.m4 shadow/
COPY shadow_diff.php shadow/
COPY tests shadow/
COPY sugarcrm shadow/
RUN cd shadow && \
    phpize && \
    ./configure && \
    make && \
    make install && \
    echo "extension=${PHP_EXT_DIR}/shadow.so" > ${PHP_CONF_DIR}/shadow.ini
RUN cd shadow && \
    php run-tests.php --show-diff .
