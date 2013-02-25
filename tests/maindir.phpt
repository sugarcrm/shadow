--TEST--
Check directory listing for main dir
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php
$template = "templatedir";
$instance = "instance";
chdir("instance");
shadow($template, $instance, array("cache", "custom"));

$iter = new DirectoryIterator("./");
$filenames = array();
foreach($iter as $item) {
        if($item->isDot()) continue;
        $filenames[] = $item->getFilename();
}
sort($filenames);
var_dump($filenames);
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
  string(17) "template_only.php"
  [7]=>
  string(8) "templdir"
  [8]=>
  string(9) "templdir2"
  [9]=>
  string(8) "test.php"
  [10]=>
  string(12) "tinclude.php"
  [11]=>
  string(3) "txt"
  [12]=>
  string(14) "unwritable.txt"
  [13]=>
  string(7) "urw.php"
}
