--TEST--
Check touching files with stream
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php 
require_once('setup.inc');

class MyStream {
	 public function stream_open($path, $mode)
    	{
		$this->fp = fopen(substr($path, 11), $mode);	
	}
	public function stream_metadata($path) {
		touch(substr($path, 11));
	}
}	
stream_register_wrapper('mystream', 'MyStream');

touch("mystream://$template/txt/instream.txt");
shadow("",""); // disable shadowing
var_dump(file_exists("$instance/txt/instream.txt"));
unlink("$instance/txt/instream.txt");

?>
--EXPECT--
bool(true)
