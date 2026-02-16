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
