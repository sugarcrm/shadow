--TEST--
Check writing to files in new dir
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

file_put_contents("$template/txt/new/dir/_twrite.txt", "writing as template\n");
file_put_contents("$template/templdir/new/dir/_twrite.txt", "writing as template\n");
file_put_contents("$instance/templdir2/new/dir/_twrite.txt", "writing as instance\n");

var_dump(file_exists("$template/txt/new/dir/_twrite.txt"));
var_dump(file_exists("$template/templdir/new/dir/_twrite.txt"));
var_dump(file_exists("$instance/templdir/new/dir/_twrite.txt"));
var_dump(file_exists("$template/templdir2/new/dir/_twrite.txt"));
var_dump(file_exists("$instance/templdir2/new/dir/_twrite.txt"));

shadow("",""); // disable shadowing
echo "Unshadow\n";
var_dump(file_exists("$template/txt/new/dir/_twrite.txt"));
var_dump(file_exists("$instance/txt/new/dir/_twrite.txt"));

var_dump(file_exists("$template/templdir/new/dir/_twrite.txt"));
var_dump(file_exists("$instance/templdir/new/dir/_twrite.txt"));
var_dump(file_exists("$template/templdir2/new/dir/_twrite.txt"));
var_dump(file_exists("$instance/templdir2/new/dir/_twrite.txt"));

unlink("$instance/txt/new/dir/_twrite.txt");
rmdir("$instance/txt/new/dir");
rmdir("$instance/txt/new");

unlink("$instance/templdir/new/dir/_twrite.txt");
unlink("$instance/templdir2/new/dir/_twrite.txt");
rmdir("$instance/templdir/new/dir");
rmdir("$instance/templdir/new");
rmdir("$instance/templdir");
rmdir("$instance/templdir2/new/dir");
rmdir("$instance/templdir2/new");
rmdir("$instance/templdir2");

?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
Unshadow
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
