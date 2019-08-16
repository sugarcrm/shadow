--TEST--
Check globs
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');
$w = getcwd();
chdir($template);
$res = glob("txt/*.txt");
sort($res);
var_dump($res);
chdir($w);
chdir($instance);
$res = glob("txt/*.txt");
sort($res);
var_dump($res);
$res = glob("templdir/*");
var_dump($res);
$res = glob("instdir/*");
var_dump($res);
$res = glob("cache/*");
var_dump($res);
$res = glob("*");
sort($res);
var_dump($res);

$res = glob("**");
sort($res);
var_dump($res);

chdir($w);
$res = glob("*");
sort($res);
var_dump($res);


?>
--EXPECT--
array(3) {
  [0]=>
  string(13) "txt/ifile.txt"
  [1]=>
  string(16) "txt/override.txt"
  [2]=>
  string(13) "txt/tfile.txt"
}
array(3) {
  [0]=>
  string(13) "txt/ifile.txt"
  [1]=>
  string(16) "txt/override.txt"
  [2]=>
  string(13) "txt/tfile.txt"
}
array(1) {
  [0]=>
  string(14) "templdir/t.txt"
}
array(1) {
  [0]=>
  string(13) "instdir/t.txt"
}
array(1) {
  [0]=>
  string(15) "cache/cache.txt"
}
array(9) {
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
  string(8) "test.php"
  [8]=>
  string(3) "txt"
}
array(9) {
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
  string(8) "test.php"
  [8]=>
  string(3) "txt"
}
array(2) {
  [0]=>
  string(8) "instance"
  [1]=>
  string(11) "templatedir"
}
