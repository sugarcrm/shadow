--TEST--
Check writing to files
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

file_put_contents("$template/txt/_twrite.txt", "writing as template\n");
file_put_contents("$instance/txt/_iwrite.txt", "writing as instance\n");

var_dump(file_exists("$template/txt/_twrite.txt"));
var_dump(file_exists("$template/txt/_iwrite.txt"));
var_dump(file_exists("$instance/txt/_twrite.txt"));
var_dump(file_exists("$instance/txt/_iwrite.txt"));

echo file_get_contents("$template/txt/_twrite.txt");
echo file_get_contents("$instance/txt/_twrite.txt");
echo file_get_contents("$template/txt/_iwrite.txt");
echo file_get_contents("$instance/txt/_iwrite.txt");

shadow("",""); // disable shadowing

var_dump(file_exists("$instance/txt/_twrite.txt"));
var_dump(file_exists("$instance/txt/_iwrite.txt"));
var_dump(file_exists("$template/txt/_twrite.txt"));
var_dump(file_exists("$template/txt/_iwrite.txt"));

@unlink("$template/txt/_twrite.txt");
@unlink("$template/txt/_iwrite.txt");
@unlink("$instance/txt/_twrite.txt");
@unlink("$instance/txt/_iwrite.txt");

?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(true)
writing as template
writing as template
writing as instance
writing as instance
bool(true)
bool(true)
bool(false)
bool(false)
