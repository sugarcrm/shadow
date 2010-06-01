--TEST--
Check working with directories
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

mkdir("$template/txt/somedir");
mkdir("$template/txt/otherdir");
file_put_contents("$template/txt/somedir/_twrite.txt", "writing as template\n");

var_dump(file_exists("$template/txt/somedir"));
var_dump(file_exists("$template/txt/otherdir"));
var_dump(file_exists("$template/txt/somedir/_twrite.txt"));

unlink("$template/txt/somedir/_twrite.txt");
rmdir("$template/txt/somedir");

shadow("",""); // disable shadowing

var_dump(file_exists("$template/txt/otherdir"));
var_dump(file_exists("$instance/txt/otherdir"));
rmdir("$instance/txt/otherdir");

?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(false)
bool(true)
