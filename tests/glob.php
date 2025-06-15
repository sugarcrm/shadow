<?php
ini_set('shadow.debug', 16383); // Enable all flags EXCEPT SHADOW_DEBUG_GLOB
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
