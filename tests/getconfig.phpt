--TEST--
Check shadow_get_config
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');
var_dump(shadow_get_config());
?>
--EXPECTF--
array(3) {
  ["template"]=>
  string(%d) "%s/templatedir"
  ["instance"]=>
  string(%d) "%s/instance"
  ["instance_only"]=>
  array(3) {
    [0]=>
    string(5) "cache"
    [1]=>
    string(6) "custom"
    [2]=>
    string(31) "custom/some/long/directory/name"
  }
}
