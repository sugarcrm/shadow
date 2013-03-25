<?php
function help()
{
	$h = <<<END
Use:
php shadow_diff.php original_dir template_dir shadow_dir


END;
	print $h;
	exit(1); 
}

function error($msg)
{
	fwrite(STDERR, $msg."\n");
	exit(1);
}


// only run from command line
$sapi_type = php_sapi_name();
if(isset($_SERVER['HTTP_USER_AGENT']) || substr($sapi_type, 0, 3) != 'cli') {
	error('This utility may only be run from the command line or command prompt.');
}

if($argc < 4) {
	help();
}
list($a0, $original_dir, $template_dir, $shadow_dir) = $argv;
if(!is_dir($original_dir)) {
	error("No such directory: $original_dir");	
}
// cut left slashes to be sure path doesn't end with the slash, removes ambiguity
$original_dir = rtrim(realpath($original_dir), "/\\");

if(!is_dir($template_dir)) {
	error("No such directory: $template_dir");
}

if(!is_dir($shadow_dir)) {
	mkdir($shadow_dir, 0755, true);
	if(is_dir($shadow_dir)) {
		error("No such directory: $template_dir");
	}
}

$cut = strlen($original_dir)+1; // one more for /
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($original_dir,
		FilesystemIterator::SKIP_DOTS)) as $pathname => $fileInfo) {
		$relname = substr($pathname, $cut);
		//echo "$pathname => $relname\n";
		if(is_dir($pathname)) {
			if(file_exists("$template_dir/$relname")) continue;
			mkdir("$shadow_dir/$relname", 0755, true);
			continue;
		}
		if(file_exists("$template_dir/$relname") && md5_file("$template_dir/$relname") === md5_file($pathname)) {
			// the same file, keep it
			//echo "Same $template_dir/$relname\n";
			continue;
		}
		$tname = "$shadow_dir/$relname";
		$tdir = dirname($tname);
		if(!is_dir($tdir)) {
			mkdir($tdir, 0755, true);
		}
		if(!copy($pathname, $tname)) {
			error("Failed to copy $pathname to $tname");
		}
}
exit(0);