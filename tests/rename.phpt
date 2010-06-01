--TEST--
Check renaming
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

file_put_contents("$template/txt/_name1.txt", "writing as template\n");
rename("$template/txt/_name1.txt", "$template/txt/_name2.txt");

var_dump(file_exists("$template/txt/_name1.txt"));
var_dump(file_exists("$template/txt/_name2.txt"));

shadow("",""); // disable shadowing
var_dump(file_exists("$template/txt/_name2.txt"));
var_dump(file_exists("$instance/txt/_name2.txt"));

unlink("$instance/txt/_name2.txt");

?>
--EXPECT--
bool(false)
bool(true)
bool(false)
bool(true)
