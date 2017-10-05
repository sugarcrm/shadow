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
array(42) {
  [0]=>
  string(4) "TODO"
  [1]=>
  string(10) "chgrp.phpt"
  [2]=>
  string(10) "chmod.phpt"
  [3]=>
  string(10) "chown.phpt"
  [4]=>
  string(8) "dir.phpt"
  [5]=>
  string(9) "dirs.phpt"
  [6]=>
  string(11) "exists.phpt"
  [7]=>
  string(10) "fread.phpt"
  [8]=>
  string(14) "getconfig.phpt"
  [9]=>
  string(8) "glob.php"
  [10]=>
  string(9) "glob.phpt"
  [11]=>
  string(8) "instance"
  [12]=>
  string(16) "is_writable.phpt"
  [13]=>
  string(12) "maindir.phpt"
  [14]=>
  string(10) "mkdir.phpt"
  [15]=>
  string(11) "mkdir2.phpt"
  [16]=>
  string(11) "mkdir3.phpt"
  [17]=>
  string(13) "override.phpt"
  [18]=>
  string(19) "override_class.phpt"
  [19]=>
  string(9) "read.phpt"
  [20]=>
  string(16) "read_custom.phpt"
  [21]=>
  string(12) "readdir.phpt"
  [22]=>
  string(18) "realpath-open.phpt"
  [23]=>
  string(11) "rename.phpt"
  [24]=>
  string(12) "require.phpt"
  [25]=>
  string(17) "require_once.phpt"
  [26]=>
  string(18) "require_once2.phpt"
  [27]=>
  string(18) "require_once3.phpt"
  [28]=>
  string(25) "require_once_opcache.phpt"
  [29]=>
  string(9) "setup.inc"
  [30]=>
  string(20) "shadow_settings.phpt"
  [31]=>
  string(11) "stream.phpt"
  [32]=>
  string(11) "templatedir"
  [33]=>
  string(10) "touch.phpt"
  [34]=>
  string(11) "unlink.phpt"
  [35]=>
  string(12) "unlink2.phpt"
  [36]=>
  string(17) "write-rename.phpt"
  [37]=>
  string(10) "write.phpt"
  [38]=>
  string(17) "write_custom.phpt"
  [39]=>
  string(14) "write_new.phpt"
  [40]=>
  string(15) "write_new2.phpt"
  [41]=>
  string(19) "write_new_mask.phpt"
}
