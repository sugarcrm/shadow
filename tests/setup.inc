<?php
$template = "template";
$instance = "instance";

set_include_path(
	get_include_path().PATH_SEPARATOR.dirname(__FILE__)."/template"
);
chdir(dirname(__FILE__));

shadow($template, $instance, array("cache", "custom"));
