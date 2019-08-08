--TEST--
Check creating directories when shadow is not enabled
--SKIPIF--
<?php if (!extension_loaded('shadow')) {
    print 'skip';
} ?>
--FILE--
<?php
$instance = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'instance';
mkdir("$instance/cache/upgrades");

echo "Created\n";

rmdir("$instance/cache/upgrades");
?>
--EXPECT--
Created
