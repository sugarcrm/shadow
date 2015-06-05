--TEST--
Check overrides
--EXTENSIONS--
gd
fileinfo
--INI--
shadow.override=imagepng@w1
--SKIPIF--
<?php if (!extension_loaded('shadow')) {
    print 'skip';
} ?>
<?php if (!extension_loaded('gd')) {
    if (!function_exists('dl') || (function_exists('dl') && !dl('gd.so')) || !function_exists('imagecreate')) {
        print 'skip';
    }
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
