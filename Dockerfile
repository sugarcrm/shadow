FROM amazonlinux:2

ARG PHP_BUILD_DIR=/var/task
ARG PHP_CONF_DIR=/etc/php.d
ARG PHP_EXT_DIR=/usr/lib64/php/modules

RUN yum install -y amazon-linux-extras
RUN amazon-linux-extras enable php8.2
RUN amazon-linux-extras install -y php8.2
RUN yum clean all && \
    yum -y upgrade && \
    yum -y install ilibzip-dev libonig-dev putils gcc make \
    yum -y re2c \
    yum-utils

RUN yum -y install php-cli php-common php-devel && \
    yum clean all

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
