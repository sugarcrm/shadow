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
array(15) {
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
  string(12) "manifest.php"
  [6]=>
  string(10) "nowritedir"
  [7]=>
  string(23) "opcache-override-me.php"
  [8]=>
  string(17) "template_only.php"
  [9]=>
  string(8) "templdir"
  [10]=>
  string(9) "templdir2"
  [11]=>
  string(8) "test.php"
  [12]=>
  string(12) "tinclude.php"
  [13]=>
  string(3) "txt"
  [14]=>
  string(14) "unwritable.txt"
}
