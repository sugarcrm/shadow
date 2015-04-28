--TEST--
Check creating directories
--SKIPIF--
<?php if (!extension_loaded('shadow')) {
    print 'skip';
} ?>
--FILE--
<?php
require_once 'setup.inc';

var_dump(file_exists("$template/templdir"));
var_dump(file_exists("$instance/templdir"));

mkdir("$template/../outside/"); // $template is not writable
mkdir("$template/../outside/testdir");
mkdir("$instance/../outside/testdir2");

mkdir("$instance/templdir/deepdir1/deepdir2");
mkdir("$template/templdir2/deepdir1/deepdir2");
echo "Created\n";
var_dump(file_exists("$instance/templdir/deepdir1/deepdir2"));
var_dump(file_exists("$template/templdir/deepdir1/deepdir2"));
var_dump(file_exists("$instance/templdir2/deepdir1/deepdir2"));
var_dump(file_exists("$template/templdir2/deepdir1/deepdir2"));

shadow('', '');
echo "Unshadow\n";
var_dump(file_exists('outside/testdir'));
var_dump(file_exists('outside/testdir2'));
rmdir('outside/testdir');
rmdir('outside/testdir2');

var_dump(file_exists("$instance/templdir/deepdir1/deepdir2"));
var_dump(file_exists("$template/templdir/deepdir1/deepdir2"));
var_dump(file_exists("$instance/templdir2/deepdir1/deepdir2"));
var_dump(file_exists("$template/templdir2/deepdir1/deepdir2"));

rmdir("$instance/templdir2/deepdir1/deepdir2");
rmdir("$instance/templdir2/deepdir1");
rmdir("$instance/templdir2");
rmdir("$instance/templdir/deepdir1/deepdir2");
rmdir("$instance/templdir/deepdir1");
rmdir("$instance/templdir");
rmdir("$template/../outside/");
?>
--EXPECT--
bool(true)
bool(true)
Created
bool(true)
bool(true)
bool(true)
bool(true)
Unshadow
bool(true)
bool(true)
bool(true)
bool(false)
bool(true)
bool(false)
