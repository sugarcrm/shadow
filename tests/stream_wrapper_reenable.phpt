--TEST--
Check reading from files
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');
echo file_get_contents("$template/txt/override.txt");

unlink("$instance/txt/override.txt");

stream_wrapper_unregister('file');
stream_wrapper_restore('file');

shadow($template, $instance, array("cache", "custom", "custom/some/long/directory/name"), true) || die("failed to setup shadow");

echo file_get_contents("$instance/txt/override.txt");
?>
--EXPECT--
Instance data
Template data
