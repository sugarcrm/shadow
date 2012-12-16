--TEST--
Check listing directory content
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

function dirread($dir) {
if ($dh = dir($dir)) {
	while (($file = $dh->read()) !== false) {
    	echo "filename: $file : filetype: " . filetype("$dir/$file") . "\n";
    }
}
}
chdir($instance);
dirread("txt");
dirread("custom");
dirread("custom/subcustom");
?>
--EXPECT--
filename: . : filetype: dir
filename: .. : filetype: dir
filename: override.txt : filetype: file
filename: tfile.txt : filetype: file
filename: ifile.txt : filetype: file
filename: . : filetype: dir
filename: .. : filetype: dir
filename: instc.txt : filetype: file
filename: subcustom : filetype: dir
filename: . : filetype: dir
filename: .. : filetype: dir
filename: instance.txt : filetype: file

