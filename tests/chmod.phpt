--TEST--
Check chmod()
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

touch("$template/txt/new/dir/_twrite.txt");
chmod("$template/txt/new/dir/_twrite.txt", 0400);
var_dump(is_writable("$template/txt/new/dir/_twrite.txt"));
clearstatcache();
chmod("$template/txt/new/dir/_twrite.txt", 0600);
var_dump(is_writable("$template/txt/new/dir/_twrite.txt"));

shadow("",""); // disable shadowing
echo "Unshadow\n";
var_dump(file_exists("$template/txt/new/dir/_twrite.txt"));
var_dump(file_exists("$instance/txt/new/dir/_twrite.txt"));
$s = stat("$instance/txt/new/dir/_twrite.txt");
echo decoct($s["mode"] & 0777);
unlink("$instance/txt/new/dir/_twrite.txt");
rmdir("$instance/txt/new/dir");
rmdir("$instance/txt/new");

?>
--EXPECT--
bool(true)
bool(true)
Unshadow
bool(false)
bool(true)
600
