FROM rockylinux:9

# Set arguments for directory paths and PHP version
ARG PHP_VERSION=8.5
ARG PHP_BUILD_DIR=/var/task
ARG PHP_CONF_DIR=/etc/php.d
ARG PHP_EXT_DIR=/usr/lib64/php/modules

# Enable CRB and install required tools, resolving conflicts
RUN dnf -y update && \
    dnf -y install dnf-plugins-core && \
    dnf config-manager --set-enabled crb && \
    dnf -y install curl --allowerasing && \
    dnf -y install dnf-utils wget tar gcc make libxml2-devel \
                   bzip2 bzip2-devel libpng-devel libjpeg-devel \
                   freetype-devel oniguruma-devel libzip-devel zlib-devel

# Enable Remi repository for specified PHP version (default 8.5)
RUN dnf -y install https://rpms.remirepo.net/enterprise/remi-release-9.rpm && \
    dnf module reset php -y && \
    dnf module enable php:remi-${PHP_VERSION} -y && \
    dnf -y install php php-cli php-devel php-pear

# Prepare directories for building the PHP extension
RUN mkdir -p ${PHP_BUILD_DIR}/shadow

# Copy extension source files
COPY shadow.c php_shadow.h shadow_cache.c shadow_cache.h config.m4 ${PHP_BUILD_DIR}/shadow/
COPY tests ${PHP_BUILD_DIR}/shadow/tests/
COPY shadow_diff.php ${PHP_BUILD_DIR}/shadow/
COPY sugarcrm ${PHP_BUILD_DIR}/shadow/sugarcrm/

# Build and install the PHP extension
RUN cd ${PHP_BUILD_DIR}/shadow && \
    phpize && \
    ./configure && \
    make && \
    make install && \
    echo "extension=shadow.so" > ${PHP_CONF_DIR}/shadow.ini

# Run tests for the PHP extension
RUN cd ${PHP_BUILD_DIR}/shadow && \
    php run-tests.php --show-diff .
