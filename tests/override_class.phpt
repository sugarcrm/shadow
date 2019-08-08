--TEST--
Check overrides
--EXTENSIONS--
zip
--DESCRIPTION--
To test it locally if you have shared zip extension you'll need:
export TEST_PHP_ARGS="-d extension=$(php-config --extension-dir)/zip.so"
--INI--
shadow.override=ziparchive::open@w0,ziparchive::addfile@0
--SKIPIF--
<?php if (!extension_loaded("shadow") || !extension_loaded("zip")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');
chdir("templatedir");
$zip = new ZipArchive();
$zip->open("instdir/test.zip", ZIPARCHIVE::CREATE|ZIPARCHIVE::OVERWRITE);
$zip->addEmptyDir("txt/");
$zip->addFile("txt/ifile.txt", "txt/ifile.txt");
$zip->close();
shadow("","");
chdir($topdir);
var_dump(file_exists("instance/instdir/test.zip"));
$zip->open("instance/instdir/test.zip");
var_dump($zip->getNameIndex(0));
var_dump($zip->getNameIndex(1));
unlink('instance/instdir/test.zip');
?>
--EXPECT--
bool(true)
string(4) "txt/"
string(13) "txt/ifile.txt"
