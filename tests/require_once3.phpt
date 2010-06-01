--TEST--
Check require_once with other requires
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

require_once("$template/iinclude.php");
require_once("$instance/iinclude.php");

?>
--EXPECT--
Instance rules!
Template rules!
I am instance!
