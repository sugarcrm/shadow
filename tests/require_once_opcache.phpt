--TEST--
Check require_once with opcache
--DESCRIPTION--
This will optionally test with opcache enabled.
To use it, install ZendOpcache if needed and then do this before running tests:
export TEST_PHP_ARGS="-d zend_extension=$(php-config --extension-dir)/opcache.so"
--INI--
opcache.enable_cli=1
opcache.revalidate_path=1
opcache.revalidate_freq=0
opcache.file_update_protection=0
opcache.use_cwd=1
opcache.validate_timestamps=1
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
<?php if (!extension_loaded("Zend OPcache")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

chdir($template);
require_once("test.php");
require_once("template_only.php");
require_once("instance_only.php");

var_dump(opcache_compile_file("opcache-override-me.php"));
file_put_contents("opcache-override-me.php", '<?php print("MORE Opcache!\n"); ?>');
require_once("opcache-override-me.php");

?>
--EXPECT--
I am instance!
Template rules!
Instance rules!
bool(true)
MORE Opcache!
--CLEAN--
<?php
require_once('setup.inc');
unlink("$instance/opcache-override-me.php");
?>
