--TEST--
Check chmod()
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

// write from $template to instance, $template isn't writable
touch("$template/txt/new/dir/_twrite.txt");
chmod("$template/txt/new/dir/_twrite.txt", 0400); // read only
var_dump(is_writable("$template/txt/new/dir/_twrite.txt"));
clearstatcache();
chmod("$template/txt/new/dir/_twrite.txt", 0600);
var_dump(is_writable("$template/txt/new/dir/_twrite.txt"));

shadow('', ''); // disable shadowing
echo "Unshadow" . PHP_EOL;
var_dump(file_exists("$template/txt/new/dir/_twrite.txt"));
var_dump(file_exists("$instance/txt/new/dir/_twrite.txt"));
$s = stat("$instance/txt/new/dir/_twrite.txt");
echo substr(decoct($s['mode']), -3);
unlink("$instance/txt/new/dir/_twrite.txt");
rmdir("$instance/txt/new/dir");
rmdir("$instance/txt/new");

?>
--EXPECT--
bool(false)
bool(true)
Unshadow
bool(false)
bool(true)
600
