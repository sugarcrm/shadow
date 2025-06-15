--TEST--
User-provided iterator test case
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php
// setup.inc is expected to call shadow() with appropriate paths
// For this test, we will manually set them up to point to user_fixtures
ini_set('shadow.enabled', '1');
ini_set('shadow.debug', SHADOW_DEBUG_PATHCHECK | SHADOW_DEBUG_STAT | SHADOW_DEBUG_OPENDIR | SHADOW_DEBUG_RESOLVE | SHADOW_DEBUG_FAIL); // Enable relevant debug flags

$template_path = __DIR__ . '/user_fixtures/templatedir';
$instance_path = __DIR__ . '/user_fixtures/instance';

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

// User mentioned iterating $template/qwe and $template/api
$topLevelDirs = [
    $template_path . "/qwe",
    $template_path . "/api"
];

foreach ($topLevelDirs as $dir_to_iterate) {
    echo "
Iterating directory (original path given to iterator): $dir_to_iterate
";

    if (!file_exists($dir_to_iterate)) {
        // If the template path itself doesn't exist, shadow might still make it iterable
        // if the corresponding instance path exists (e.g. template/qwe -> instance/qwe)
        echo "Note: Base directory $dir_to_iterate does not physically exist in template. Shadow behavior will apply.
";
    } elseif (!is_dir($dir_to_iterate)) {
         echo "Warning: Base directory $dir_to_iterate exists but is NOT a directory according to is_dir(). Iterator might fail.
";
    }

    $count = 0;
    $items = [];
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir_to_iterate, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS | FilesystemIterator::CURRENT_AS_FILEINFO),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $pathname = $item->getPathname();
            $type = $item->isDir() ? 'Dir' : ($item->isFile() ? 'File' : 'Other');
            $items[] = "Item: " . $pathname . " (Type: " . $type . ", isLink: " . ($item->isLink()?'Yes':'No') . ")";
            $count++;
        }
        echo "Found $count items in $dir_to_iterate:
";
        sort($items); // Sort for consistent output
        foreach($items as $line) {
            echo $line . "
";
        }

    } catch (UnexpectedValueException $e) {
        echo "ERROR creating RecursiveDirectoryIterator for $dir_to_iterate: " . $e->getMessage() . "
";
    } catch (Exception $e) {
        echo "ERROR iterating $dir_to_iterate: " . $e->getMessage() . "
";
    }
}

?>
--EXPECTF--
This will be filled after observing the output.
