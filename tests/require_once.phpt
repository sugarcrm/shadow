--TEST--
Check require_once
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

require_once("$template/test.php");
require_once("$template/template_only.php");
require_once("$template/instance_only.php");

require_once("$instance/test.php");
require_once("$instance/template_only.php");
require_once("$instance/instance_only.php");
?>
--EXPECT--
I am instance!
Template rules!
Instance rules!
