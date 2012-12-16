--TEST--
Check write+rename
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');
chdir($template);
var_dump(file_exists("txt/newfile_wr.txt"));
file_put_contents("txt/newfile_wr.txt", "TEST");
rename("txt/newfile_wr.txt", "txt/newfile_wr2.txt");
var_dump(file_exists("txt/newfile_wr2.txt"));
unlink("txt/newfile_wr2.txt");
?>
--EXPECT--
bool(false)
bool(true)
