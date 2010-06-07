--TEST--
Check touching files
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

touch("$template/txt/new/dir/_twrite.txt");
touch("$template/templdir/new/dir/_twrite.txt");
touch("$instance/templdir2/new/dir/_twrite.txt");

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
