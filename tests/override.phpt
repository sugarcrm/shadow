--TEST--
Check overrides
--EXTENSIONS--
gd
fileinfo
--DESCRIPTION--
To test it locally if you have shared gd extension you'll need:
export TEST_PHP_ARGS="-d extension=$(php-config --extension-dir)/gd.so"
--INI--
shadow.override=imagepng@w1
--SKIPIF--
<?php if (!extension_loaded('shadow') || !extension_loaded('gd')) {
    print 'skip';
} ?>
--FILE--
<?php
require_once 'setup.inc';
$w = getcwd();
file_put_contents("$instance/txt/sometext.txt", 'some text for some test');
chdir($template);
$finfo = finfo_open(FILEINFO_MIME_TYPE);
echo finfo_file($finfo, 'txt/sometext.txt') . PHP_EOL;
$im = imagecreate(110, 20);
$background_color = imagecolorallocate($im, 0, 0, 0);
$text_color = imagecolorallocate($im, 233, 14, 91);
imagestring($im, 1, 5, 5, 'A Simple Text String', $text_color);
imagepng($im, 'image.png');
echo finfo_file($finfo, 'image.png') . PHP_EOL;
shadow('', '');
chdir($w);
var_dump(file_exists("$instance/txt/sometext.txt"));
unlink("$instance/txt/sometext.txt");
var_dump(file_exists("$instance/image.png"));
var_dump(unlink("$instance/image.png"));
?>
--EXPECT--
text/plain
image/png
bool(true)
bool(true)
bool(true)
