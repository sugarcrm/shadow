--TEST--
Check reading from files
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');
echo file_get_contents("$template/txt/tfile.txt");
echo file_get_contents("$template/txt/ifile.txt");
echo file_get_contents("$template/txt/override.txt");

echo file_get_contents("$instance/txt/tfile.txt");
echo file_get_contents("$instance/txt/ifile.txt");
echo file_get_contents("$instance/txt/override.txt");
?>
--EXPECT--
Here's some template data
Here's some instance data
Instance data
Here's some template data
Here's some instance data
Instance data
