--TEST--
Check unlinking files
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

file_put_contents("$template/txt/_utwrite.txt", "writing as template\n");
file_put_contents("$instance/txt/_uiwrite.txt", "writing as instance\n");

var_dump(file_exists("$template/txt/_utwrite.txt"));
var_dump(file_exists("$template/txt/_uiwrite.txt"));
var_dump(file_exists("$instance/txt/_utwrite.txt"));
var_dump(file_exists("$instance/txt/_uiwrite.txt"));

unlink("$template/txt/_uiwrite.txt");

var_dump(file_exists("$template/txt/_utwrite.txt"));
var_dump(file_exists("$instance/txt/_utwrite.txt"));
var_dump(file_exists("$template/txt/_uiwrite.txt"));
var_dump(file_exists("$instance/txt/_uiwrite.txt"));

unlink("$template/txt/_utwrite.txt");

var_dump(file_exists("$template/txt/_utwrite.txt"));
var_dump(file_exists("$template/txt/_uiwrite.txt"));
var_dump(file_exists("$instance/txt/_utwrite.txt"));
var_dump(file_exists("$instance/txt/_uiwrite.txt"));

?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
