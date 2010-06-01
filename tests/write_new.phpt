--TEST--
Check writing to files
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

file_put_contents("$template/txt/new/dir/_twrite.txt", "writing as template\n");

var_dump(file_exists("$template/txt/new/dir/_twrite.txt"));

shadow("",""); // disable shadowing

var_dump(file_exists("$template/txt/new/dir/_twrite.txt"));
var_dump(file_exists("$instance/txt/new/dir/_twrite.txt"));

unlink("$instance/txt/new/dir/_twrite.txt");
rmdir("$instance/txt/new/dir");
rmdir("$instance/txt/new");

?>
--EXPECT--
bool(true)
bool(false)
bool(true)
