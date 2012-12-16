--TEST--
Check realpath+open (issue 2)
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');
var_dump(realpath("$template/template_only.php"));
file_get_contents("$template/template_only.php");
echo "OK\n";
?>
--EXPECTF--
string(%d) "%s/templatedir/template_only.php"
OK
