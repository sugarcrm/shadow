FROM rockylinux:9

# Set arguments for directory paths
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

# Enable Remi repository for PHP 8.4
RUN dnf -y install https://rpms.remirepo.net/enterprise/remi-release-9.rpm && \
    dnf module reset php -y && \
    dnf module enable php:remi-8.4 -y && \
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

# Create a directory for the iterator test script
RUN mkdir -p ${PHP_BUILD_DIR}/shadow/test_iterator

# Create the iterator test script
RUN cat <<'EOF' > ${PHP_BUILD_DIR}/shadow/test_iterator/test.php
<?php
// Create a dummy directory structure
mkdir('test_dir');
mkdir('test_dir/subdir1');
touch('test_dir/file1.txt');
touch('test_dir/subdir1/file2.txt');

$iterator = new RecursiveDirectoryIterator('test_dir', RecursiveDirectoryIterator::SKIP_DOTS);
$recursiveIterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);

foreach ($recursiveIterator as $fileInfo) {
    echo $fileInfo->getPathname() . PHP_EOL;
}

// Clean up the dummy directory structure
unlink('test_dir/subdir1/file2.txt');
rmdir('test_dir/subdir1');
unlink('test_dir/file1.txt');
rmdir('test_dir');
?>
EOF

# Run the iterator test script
RUN cd ${PHP_BUILD_DIR}/shadow/test_iterator && php test.php
