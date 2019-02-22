<?php
$topdir = dirname(__FILE__);
$template = join(DIRECTORY_SEPARATOR, [$topdir, "templatedir"]);
$instance = join(DIRECTORY_SEPARATOR, [$topdir, "instance"]);

set_include_path(
	get_include_path().PATH_SEPARATOR.$template
);

chdir($topdir);
shadow($template, $instance, array("cache", "custom", "custom/some/long/directory/name")) || die("failed to setup shadow");
