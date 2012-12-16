<?php
$template = "templatedir";
$instance = "instance";

set_include_path(
	get_include_path().PATH_SEPARATOR.dirname(__FILE__)."/templatedir"
);
chdir(dirname(__FILE__));

shadow($template, $instance, array("cache", "custom"));
