--TEST--
Check file_exists
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');
var_dump(file_exists("$template/txt/tfile.txt"));
var_dump(file_exists("$template/txt/ifile.txt"));
var_dump(file_exists("$template/txt/override.txt"));

var_dump(file_exists("$instance/txt/tfile.txt"));
var_dump(file_exists("$instance/txt/ifile.txt"));
var_dump(file_exists("$instance/txt/override.txt"));

var_dump(file_exists("$template/txt/nosuchfile.txt"));
var_dump(file_exists("$instance/txt/nosuchfile.txt"));
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
