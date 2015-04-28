--TEST--
Check writing to files in new dir with dir mask
--INI--
shadow.mkdir_mask = 0755
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
error_reporting(-1);
ini_set('display_errors', 1);

mkdir("$template/txt/new/dir/");
mkdir("$template/txt/new2/dir/");

file_put_contents("$template/txt/new/dir/_twrite.txt", "writing as template\n");
ini_set('shadow.mkdir_mask', '0700');
file_put_contents("$template/txt/new2/dir/_twrite.txt", "writing as template\n");
mkdir("$template/txt/new2/dir7000/");

clearstatcache();

$stat = stat("$template/txt/new/dir/");
echo substr(decoct($stat['mode']), -5) . PHP_EOL;
$stat = stat("$template/txt/new");
echo substr(decoct($stat['mode']), -5) . PHP_EOL;
$stat = stat("$template/txt/new2/dir/");
echo substr(decoct($stat['mode']), -5) . PHP_EOL;
$stat = stat("$template/txt/new2");
echo substr(decoct($stat['mode']), -5) . PHP_EOL;
$stat = stat("$template/txt/new2/dir7000/");
echo substr(decoct($stat['mode']), -5) . PHP_EOL;
$stat = stat("$instance/txt/new2/dir7000/");
echo substr(decoct($stat['mode']), -5) . PHP_EOL;

unlink("$template/txt/new/dir/_twrite.txt");
rmdir("$instance/txt/new/dir");
var_dump(rmdir("$instance/txt/new"));
unlink("$instance/txt/new2/dir/_twrite.txt");
rmdir("$template/txt/new2/dir7000/");
rmdir("$instance/txt/new2/dir");
var_dump(rmdir("$instance/txt/new2"));
?>
--EXPECT--
40755
40755
40700
40700
40700
40700
bool(true)
bool(true)
