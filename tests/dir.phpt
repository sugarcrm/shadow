--TEST--
Check listing directory content
--SKIPIF--
<?php if (!extension_loaded('shadow')) {
    print 'skip';
} ?>
--FILE--
<?php
require_once 'setup.inc';

function dirread($dir)
{
    $aTmp = array();
    if (($dh = dir($dir)) == true) {
        while (($file = $dh->read()) !== false) {
            $aTmp[] = "filename: $file : filetype: " . filetype("$dir/$file") . PHP_EOL;
        }
    }
    sort($aTmp);
    foreach ($aTmp as $key => $value) {
        echo $value;
    }
}
chdir($instance);
dirread('txt');
dirread('custom');
dirread('custom/subcustom');
?>
--EXPECT--
filename: . : filetype: dir
filename: .. : filetype: dir
filename: ifile.txt : filetype: file
filename: override.txt : filetype: file
filename: tfile.txt : filetype: file
filename: . : filetype: dir
filename: .. : filetype: dir
filename: instc.txt : filetype: file
filename: subcustom : filetype: dir
filename: . : filetype: dir
filename: .. : filetype: dir
filename: instance.txt : filetype: file
