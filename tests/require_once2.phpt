--TEST--
Check require_once with other requires
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

require_once("$instance/tinclude.php");
require_once("$template/tinclude.php");

?>
--EXPECT--
I am instance!
Template rules!
Instance rules!
