--TEST--
Check iterators failure (with options debug)
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php
require_once('setup.inc');

ini_set('shadow.debug', -1);
ini_set('display_errors', 1);
error_reporting(E_ALL);

$template_path = __DIR__ . '/fixtures/templatedir';
$instance_path = __DIR__ . '/fixtures/instance';

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

$topLevelDirs = [
    $template_path . "/api",
    $template_path . "/qwe",
    $template_path . "/conflict",
    $template_path . "/conflict2"
];

foreach ($topLevelDirs as $dir) {
    echo "
Iterating directory (original): $dir
";
    if (!is_dir($dir)) {
        echo "Warning: Base directory $dir is not seen as a directory by PHP's is_dir(). Iterator might fail or use shadow's fallback.
";
    }
    echo "Attempting to iterate: $dir
";
    $count = 0;
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS | FilesystemIterator::CURRENT_AS_FILEINFO),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            echo "Item: " . $item->getPathname() . " (isDir: " . ($item->isDir()?'Yes':'No') . ", isFile: " . ($item->isFile()?'Yes':'No') . ")
";
            $count++;
        }
        echo "Found $count items in $dir
";
    } catch (UnexpectedValueException $e) {
        echo "Error creating RecursiveDirectoryIterator for $dir: " . $e->getMessage() . "
";
    } catch (Exception $e) {
        echo "Error iterating $dir: " . $e->getMessage() . "
";
    }
}
?>
--EXPECT--
This will be filled once we see the actual output.
