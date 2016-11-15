--TEST--
Check chown()
--SKIPIF--
<?php if (!extension_loaded('shadow')) {
    print 'skip';
} ?>
<?php if (exec('whoami') == 'root') {
    print 'skip';
} ?>
--INI--
shadow.override=chown@0
--FILE--
<?php
require_once 'setup.inc';

$file = "txt/_chowned.txt";
$owner = getmyuid();
$oldcwd = getcwd();

chdir($template);
var_dump(touch($file));
var_dump(chown($file, $owner));

chdir($oldcwd);
shadow('', ''); // disable shadowing
echo "Unshadow" . PHP_EOL;

$stat = stat("$instance/$file");
var_dump($stat['uid'] === $owner);

unlink("$instance/$file");

?>
--EXPECT--
bool(true)
bool(true)
Unshadow
bool(true)