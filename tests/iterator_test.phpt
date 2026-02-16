--TEST--
Check iterators failure
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php
require_once('setup.inc'); // This likely sets $template and $instance

// Corrected paths based on created fixtures
$topLevelDirs = [
    "$template/api", // Should be tests/fixtures/templatedir/api
    "$instance/qwe", // Should be tests/fixtures/instance/qwe
    "$instance/api", // Should be tests/fixtures/instance/api
];

foreach ($topLevelDirs as $dirPath) {
    echo "Iterating: $dirPath\n";
    if (!is_dir($dirPath)) {
        echo "Directory $dirPath does not exist!\n";
        continue;
    }
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $count++;
        // Use a relative path for EXPECTF robustness if $template/$instance changes base
        $displayPath = $item->getPathname();
        if (strpos($displayPath, $template) === 0) {
            $displayPath = '$template' . substr($displayPath, strlen($template));
        } elseif (strpos($displayPath, $instance) === 0) {
            $displayPath = '$instance' . substr($displayPath, strlen($instance));
        }
        // Normalize slashes
        $displayPath = str_replace('\\', '/', $displayPath);


        echo sprintf("Path: %s, isDir: %d, isFile: %d\n",
            $displayPath,
            $item->isDir(),
            $item->isFile()
        );
    }
    echo "Count for $dirPath: $count\n\n";
}

?>
--EXPECTF--
Iterating: %s/templatedir/api
Path: $template/api, isDir: 1, isFile: 0
Path: $template/api/rest.php, isDir: 0, isFile: 1
Count for %s/templatedir/api: 2

Iterating: %s/instance/qwe
Path: $instance/qwe, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Accounts, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Accounts/.gitkeep, isDir: 0, isFile: 1
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Calls, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Calls/.gitkeep, isDir: 0, isFile: 1
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Cases, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Cases/.gitkeep, isDir: 0, isFile: 1
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Contacts, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Contacts/.gitkeep, isDir: 0, isFile: 1
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Documents, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Documents/.gitkeep, isDir: 0, isFile: 1
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/ExternalUsers, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/ExternalUsers/.gitkeep, isDir: 0, isFile: 1
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Meetings, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Meetings/.gitkeep, isDir: 0, isFile: 1
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Messages, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Messages/.gitkeep, isDir: 0, isFile: 1
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Notes, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Notes/.gitkeep, isDir: 0, isFile: 1
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Project, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Project/.gitkeep, isDir: 0, isFile: 1
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/PurchasedLineItems, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/PurchasedLineItems/.gitkeep, isDir: 0, isFile: 1
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Tasks, isDir: 1, isFile: 0
Path: $instance/qwe/modulebuilder/builds/qweqwe/SugarModules/modules/Tasks/.gitkeep, isDir: 0, isFile: 1
Count for %s/instance/qwe: 30

Iterating: %s/instance/api
Path: $instance/api, isDir: 1, isFile: 0
Path: $instance/api/rest.php, isDir: 0, isFile: 1
Count for %s/instance/api: 2
