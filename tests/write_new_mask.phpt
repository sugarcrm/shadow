--TEST--
Check writing to files in new dir with dir mask
--INI--
shadow.mkdir_mask = 0755
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

file_put_contents("$template/txt/new/dir/_twrite.txt", "writing as template\n");
ini_set("shadow.mkdir_mask", 0700);
file_put_contents("$template/txt/new2/dir/_twrite.txt", "writing as template\n");

$stat = stat("$template/txt/new/dir");
echo decoct($stat["mode"])."\n";
$stat = stat("$template/txt/new");
echo decoct($stat["mode"])."\n";
$stat = stat("$template/txt/new2/dir");
echo decoct($stat["mode"])."\n";
$stat = stat("$template/txt/new2");
echo decoct($stat["mode"])."\n";

shadow("",""); // disable shadowing

unlink("$instance/txt/new/dir/_twrite.txt");
rmdir("$instance/txt/new/dir");
rmdir("$instance/txt/new");
unlink("$instance/txt/new2/dir/_twrite.txt");
rmdir("$instance/txt/new2/dir");
rmdir("$instance/txt/new2");

?>
--EXPECT--
40755
40755
40700
40700
