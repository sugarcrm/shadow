<?php
require_once('setup.inc'); // This will setup shadow paths

// Enable shadow debug to see our new logs
ini_set('shadow.debug', -1); // Enable all debug flags

// Define template and instance paths based on setup.inc conventions
$template_path = __DIR__ . '/fixtures/templatedir';
$instance_path = __DIR__ . '/fixtures/instance';

// Manually call shadow() to set template and instance paths
// This is crucial for the shadow extension to know which directories to operate on.
if (function_exists('shadow')) {
    shadow($template_path, $instance_path);
    echo "Shadow paths set: Template='{$template_path}', Instance='{$instance_path}'
";
} else {
    echo "Shadow function not available. Skipping test.
";
    exit;
}

echo "Template Path (realpath): " . realpath($template_path) . "
";
echo "Instance Path (realpath): " . realpath($instance_path) . "
";

// We iterate from the *template* path perspective, shadow handles the merging.
$topLevelDirs = [
    $template_path . "/api",
    $template_path . "/qwe"
];

// Helper function to resolve the path that the iterator will actually see.
// This is a simplified simulation for logging purposes.
function get_effective_path_for_iterator($path_to_check, $template_root, $instance_root) {
    // Check if path_to_check is within the template_root
    if (strpos(realpath($path_to_check), realpath($template_root)) === 0) {
        $relative_path = substr(realpath($path_to_check), strlen(realpath($template_root)));
        $instance_equivalent = realpath($instance_root) . $relative_path;

        // If instance has this directory, it's what shadow would prioritize for some operations,
        // or merge. For RecursiveDirectoryIterator, it starts with the given path.
        // If the given path (e.g. template/qwe) doesn't exist, but instance/qwe does,
        // shadow's dir_opener might effectively open instance/qwe.
        // If template/qwe exists, it opens template/qwe (and merges content from instance/qwe).
        if (is_dir($instance_equivalent) && !is_dir($path_to_check)) {
            // This case is tricky: if template path doesn't exist but instance does.
            // shadow_dir_opener might open instance.
            // For now, let's assume the iterator is given the $path_to_check.
            // The debug logs from shadow_stat will tell us what paths it's actually stating.
        }
    }
    return $path_to_check; // Default to the path given to the iterator
}


foreach ($topLevelDirs as $dir) {
    echo "
Iterating directory (original): $dir
";

    // The path given to RecursiveDirectoryIterator is what shadow_stat will see first.
    $effective_dir = $dir; //get_effective_path_for_iterator($dir, $template_path, $instance_path);

    // Before iterating, let's check if the *effective* directory (template or instance) actually exists.
    // shadow_dir_opener has logic to open instance if template doesn't exist but instance does.
    // The RecursiveDirectoryIterator constructor will throw an error if the path is not found or not a directory.
    // Our debug logs in shadow_stat will show what paths are being queried.

    echo "Attempting to iterate: $effective_dir
";

    $count = 0;
    try {
        // We must ensure the path passed to RecursiveDirectoryIterator is what shadow expects.
        // Typically, this is a path within the "template" structure.
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($effective_dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS | FilesystemIterator::CURRENT_AS_FILEINFO),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            // $item is a SplFileInfo object
            echo "Item: " . $item->getPathname() . " (isDir: " . ($item->isDir()?'Yes':'No') . ", isFile: " . ($item->isFile()?'Yes':'No') . ")\n";
            $count++;
        }
        echo "Found $count items in $effective_dir\n";
    } catch (UnexpectedValueException $e) {
        // This exception is thrown if the path cannot be opened, e.g., it does not exist or is not a directory.
        echo "Error creating RecursiveDirectoryIterator for $effective_dir: " . $e->getMessage() . "\n";
    } catch (Exception $e) {
        echo "Error iterating $effective_dir: " . $e->getMessage() . "\n";
    }
}

?>
