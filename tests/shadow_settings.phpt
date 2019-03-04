--TEST--
Check shadow settings change in script
--INI--
shadow.mkdir_mask = 0755
--SKIPIF--
<?php if (!extension_loaded('shadow')) {
    print 'skip';
} ?>
--FILE--
<?php
require_once 'setup.inc';

var_dump(shadow_get_config());

$befor = ini_get_all('shadow');
echo $befor['shadow.mkdir_mask']['local_value'] . PHP_EOL;
ini_set('shadow.mkdir_mask', '0700');

$after = ini_get_all('shadow');
echo $after['shadow.mkdir_mask']['local_value'] . PHP_EOL;

echo shadow('', '') . PHP_EOL;
?>
--EXPECTF--
array(3) {
  ["template"]=>
  string(%d) "%s"
  ["instance"]=>
  string(%d) "%s"
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
0755
0700
1
