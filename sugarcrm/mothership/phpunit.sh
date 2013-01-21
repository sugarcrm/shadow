#!/bin/sh
# Run: phpunit.sh servername template other-args
# Example: phpunit.sh foobarbaz.msdev.sugarcrm.com /mnt/sugar/6.6.3/ent --log-junit "/mnt/sugar/shadowed/foobarbaz.msdev.sugarcrm.com/phpunit.xml"
export SERVER_NAME=$1
export REMOTE_ADDR=$1
export DOCUMENT_ROOT="$2"
export SHADOW_ROOT="$2"
# Drop first two args
shift
shift
cd /mnt/sugar/shadowed/$SERVER_NAME/tests && php -dauto_prepend_file="/mnt/sugar/SugarShadow.php" phpunit.php $*
