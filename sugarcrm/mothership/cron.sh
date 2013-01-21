#!/bin/sh
# Run: cron.sh servername template
# Example: cron.sh foobarbaz.msdev.sugarcrm.com /mnt/sugar/6.6.3/ent
export SERVER_NAME=$1
export REMOTE_ADDR=$1
export DOCUMENT_ROOT="$2"
export SHADOW_ROOT="$2"
cd /mnt/sugar/shadowed/$SERVER_NAME && php -dauto_prepend_file="/mnt/sugar/SugarShadow.php" -f $DOCUMENT_ROOT/cron.php
