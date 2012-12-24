--TEST--
Check unlinking files and then reading
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

file_put_contents("$instance/txt/tfile.txt", "writing as instance\n");
chdir($template);
var_dump(file_exists("txt/tfile.txt"));
unlink("txt/tfile.txt");
var_dump(file_exists("txt/tfile.txt"));
?>
--EXPECT--
bool(true)
bool(true)
