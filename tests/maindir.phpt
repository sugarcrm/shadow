--TEST--
Check directory listing for main dir
--SKIPIF--
<?php if (!extension_loaded('shadow')) {
    print 'skip';
} ?>
--FILE--
<?php
require_once 'setup.inc';
chdir($instance);

$iter = new DirectoryIterator('.');
$filenames = array();
foreach ($iter as $item) {
    if ($item->isDot()) {
        continue;
    }
    $filenames[] = $item->getFilename();
}
sort($filenames);
var_dump($filenames);
?>
--EXPECT--
array(14) {
  [0]=>
  string(5) "cache"
  [1]=>
  string(6) "custom"
  [2]=>
  string(12) "iinclude.php"
  [3]=>
  string(17) "instance_only.php"
  [4]=>
  string(7) "instdir"
  [5]=>
  string(10) "nowritedir"
  [6]=>
  string(23) "opcache-override-me.php"
  [7]=>
  string(17) "template_only.php"
  [8]=>
  string(8) "templdir"
  [9]=>
  string(9) "templdir2"
  [10]=>
  string(8) "test.php"
  [11]=>
  string(12) "tinclude.php"
  [12]=>
  string(3) "txt"
  [13]=>
  string(14) "unwritable.txt"
}
