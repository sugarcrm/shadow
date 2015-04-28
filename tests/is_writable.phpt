--TEST--
Check is_writable on instance
--SKIPIF--
<?php if (!extension_loaded('shadow')) {
    print 'skip';
} ?>
--FILE--
<?php
require_once 'setup.inc';
var_dump(is_writable("$template/unwritable.txt"));
var_dump(is_writable("$instance/unwritable.txt"));
var_dump(is_writable("$instance/nowritedir/nowrite_instance.txt"));
var_dump(is_writable("$instance/unwritable_no.txt"));
var_dump(is_writable("$instance/cache/nowrite_instance.txt"));
var_dump(is_writable("$template/cache/nowrite_instance.txt"));
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(false)
bool(false)
bool(false)
