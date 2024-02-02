--TEST--
Check directory mask on instance directory
--INI--
shadow.mkdir_mask=0777
--SKIPIF--
<?php if (!extension_loaded('shadow') || true) {
    print 'skip';
} ?>
--FILE--
<?php
require_once 'setup.inc';

chdir($template);
umask(0);

touch('non-existing/test.txt');

clearstatcache();

$stat = stat($instance . '/non-existing/');
echo substr(decoct($stat['mode']), -5) . PHP_EOL;

unlink('non-existing/test.txt');
rmdir($instance . '/non-existing/');

ini_set('shadow.mkdir_mask', '0700');

touch('non-existing/test.txt');

clearstatcache();

$stat = stat($instance . '/non-existing/');
echo substr(decoct($stat['mode']), -5) . PHP_EOL;

unlink('non-existing/test.txt');
rmdir($instance . '/non-existing/');
?>
--EXPECT--
40777
40700
