--TEST--
Check writing to files in new dir with dir mask
--INI--
shadow.mkdir_mask=0755
--SKIPIF--
<?php if (!extension_loaded('shadow')) {
    print 'skip';
} ?>
<?php if (exec('whoami') == 'root') {
    print 'skip';
} ?>
--FILE--
<?php
require_once 'setup.inc';

chdir($template);

mkdir("txt/new/");
mkdir("txt/new/dir/");
file_put_contents("txt/new/dir/_twrite.txt", "writing as template\n");

mkdir("txt/new2/");
mkdir("txt/new2/dir/");
ini_set('shadow.mkdir_mask', '0700');
file_put_contents("txt/new2/dir/_twrite.txt", "writing as template\n");
mkdir("txt/new2/dir7000/");

clearstatcache();

$stat = stat("txt/new/dir/");
echo substr(decoct($stat['mode']), -5) . PHP_EOL;

$stat = stat("txt/new/");
echo substr(decoct($stat['mode']), -5) . PHP_EOL;

$stat = stat("txt/new2/dir/");
echo substr(decoct($stat['mode']), -5) . PHP_EOL;

$stat = stat("txt/new2/");
echo substr(decoct($stat['mode']), -5) . PHP_EOL;

$stat = stat("txt/new2/dir7000/");
echo substr(decoct($stat['mode']), -5) . PHP_EOL;

clearstatcache();

unlink("txt/new/dir/_twrite.txt");
rmdir("txt/new/dir");
var_dump(rmdir("txt/new"));

unlink("txt/new2/dir/_twrite.txt");
rmdir("txt/new2/dir7000/");
rmdir("txt/new2/dir");
var_dump(rmdir("txt/new2"));
?>
--EXPECT--
40755
40755
40755
40755
40755
40700
bool(true)
bool(true)
