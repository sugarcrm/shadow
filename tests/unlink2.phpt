--TEST--
Check unlinking files and then reading
--SKIPIF--
<?php if (!extension_loaded("shadow")) {
    print "skip";
}?>
--FILE--
<?php
require_once('setup.inc');

file_put_contents("$instance/txt/unlink2.txt", "writing as instance\n");
chdir($template);
var_dump(file_exists("txt/unlink2.txt"));
unlink("txt/unlink2.txt");
var_dump(file_exists("txt/unlink2.txt"));
?>
--EXPECT--
bool(true)
bool(false)
