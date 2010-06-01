--TEST--
Check writing to files
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

file_put_contents("$template/cache/_twrite.txt", "writing as template\n");
file_put_contents("$instance/cache/_iwrite.txt", "writing as instance\n");

var_dump(file_exists("$template/cache/_twrite.txt"));
var_dump(file_exists("$template/cache/_iwrite.txt"));

echo file_get_contents("$template/cache/_twrite.txt");
echo file_get_contents("$instance/cache/_twrite.txt");
echo file_get_contents("$template/cache/_iwrite.txt");
echo file_get_contents("$instance/cache/_iwrite.txt");

unlink("$template/cache/_twrite.txt");
//unlink("$template/cache/_iwrite.txt");

var_dump(file_exists("$template/cache/_iwrite.txt"));
var_dump(file_exists("$template/cache/_twrite.txt"));

echo "== Unshadow\n";
shadow("", "");
var_dump(file_exists("$template/cache/_iwrite.txt"));
var_dump(file_exists("$instance/cache/_iwrite.txt"));
unlink("$instance/cache/_iwrite.txt");

var_dump(file_exists("$template/custom/_twrite.txt"));
file_put_contents("$template/custom/_twrite.txt", "writing as template\n");
clearstatcache();
var_dump(file_exists("$template/custom/_twrite.txt"));

echo "== Shadow\n";
shadow($template, $instance, array("cache", "custom"));
var_dump(file_exists("$template/custom/_twrite.txt"));
file_put_contents("$template/custom/_twrite.txt", "writing as template\n");
var_dump(file_exists("$template/custom/_twrite.txt"));
unlink("$template/custom/_twrite.txt");
shadow("", "");
unlink("$template/custom/_twrite.txt");
var_dump(file_exists("$template/custom/_twrite.txt"));
?>
--EXPECT--
bool(true)
bool(true)
writing as template
writing as template
writing as instance
writing as instance
bool(true)
bool(false)
== Unshadow
bool(false)
bool(true)
bool(false)
bool(true)
== Shadow
bool(false)
bool(true)
bool(false)
