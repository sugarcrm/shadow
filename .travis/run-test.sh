#!/bin/bash
export TEST_PHP_EXECUTABLE=`which php`
TEST_DIR="`pwd`/tests"

php_zts=$(php -r "echo PHP_ZTS;")

if [ $php_zts -eq 1 ] ; then
    echo "Skipping tests for ZTS, because we currently don't work with ZTS PHP"
    exit 0
else
    $*
fi
