FROM amazonlinux:2

ARG PHP_BUILD_DIR=/var/task
ARG PHP_CONF_DIR=/etc/php.d
ARG PHP_EXT_DIR=/usr/lib64/php/modules

RUN yum clean all && \
    yum -y upgrade && \
    yum -y install ilibzip-dev libonig-dev putils \
    yum -y install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm \
      https://rpms.remirepo.net/enterprise/remi-release-7.rpm \
      re2c \
      yum-utils && \
    yum-config-manager --disable remi-safe

RUN yum-config-manager --enable remi-php82 && \
    yum-config-manager --setopt=remi-php82.priority=10 --save && \
    yum -y install php-cli php-common php-devel && \
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
    make test && \
    echo "extension=${PHP_EXT_DIR}/shadow.so" > ${PHP_CONF_DIR}/shadow.ini

ENTRYPOINT ["php", "-m"]
