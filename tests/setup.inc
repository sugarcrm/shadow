<?php
$template = "templatedir";
$instance = "instance";
$topdir = dirname(__FILE__);
$template_path = join(DIRECTORY_SEPARATOR, [$topdir, $template]);

set_include_path(
	get_include_path().PATH_SEPARATOR.$template_path
);
chdir($topdir);

shadow($template, $instance, array("cache", "custom"));
