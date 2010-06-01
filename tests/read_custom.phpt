--TEST--
Check reading from instance-only dirs
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');
echo file_get_contents("$template/custom/custom.txt");
var_dump(file_exists("$template/custom/custom.txt"));

echo file_get_contents("$template/custom/instc.txt");
echo file_get_contents("$instance/custom/instc.txt");
echo file_get_contents("$template/cache/cache.txt");
echo file_get_contents("$instance/cache/cache.txt");
?>
--EXPECTF--
Warning: file_get_contents(template/custom/custom.txt): failed to open stream: operation failed in %s/read_custom.php on line 3
bool(false)
Instance custom!
Instance custom!
Instance cache!
Instance cache!