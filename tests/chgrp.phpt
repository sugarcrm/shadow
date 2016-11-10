--TEST--
Check chgrp()
--SKIPIF--
<?php if (!extension_loaded('shadow')) {
    print 'skip';
} ?>
<?php if (exec('whoami') == 'root') {
    print 'skip';
} ?>
--INI--
shadow.override=chgrp@0
--FILE--
<?php
require_once 'setup.inc';

$file = "txt/_chgrped.txt";
$group = getmygid();
$oldcwd = getcwd();

chdir($template);
var_dump(touch($file));
var_dump(chgrp($file, $group));

chdir($oldcwd);
shadow('', ''); // disable shadowing
echo "Unshadow" . PHP_EOL;

$stat = stat("$instance/$file");
var_dump($stat['gid'] === $group);

unlink("$instance/$file");

?>
--EXPECT--
bool(true)
bool(true)
Unshadow
bool(true)