--TEST--
Check require
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

require("$template/test.php");
require("$template/template_only.php");
require("$template/instance_only.php");

require("$instance/test.php");
require("$instance/template_only.php");
require("$instance/instance_only.php");

?>
--EXPECT--
I am instance!
Template rules!
Instance rules!
I am instance!
Template rules!
Instance rules!
