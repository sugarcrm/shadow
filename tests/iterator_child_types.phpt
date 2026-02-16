--TEST--
Check iterator child entry type correctness
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php
require_once('setup.inc'); // Sets $template and $instance

$testDirRelative = 'mixtype_test';
$iteratePath = $instance . '/' . $testDirRelative; // Iterate the instance path

echo "Iterating: $iteratePath (LEAVES_ONLY)\n";
if (!is_dir($iteratePath)) {
    echo "Directory $iteratePath does not exist!\n";
    exit;
}

$iterator_leaves = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($iteratePath, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$actual_items_leaves = [];
foreach ($iterator_leaves as $item) {
    $relativePath = str_replace($iteratePath . '/', '', str_replace('\\', '/', $item->getPathname()));
    $actual_items_leaves[] = sprintf("Path: %s, Type: %s, isDir: %d, isFile: %d",
        $relativePath,
        $item->getType(),
        $item->isDir(),
        $item->isFile()
    );
}
sort($actual_items_leaves);
foreach($actual_items_leaves as $line) {
    echo $line . "\n";
}

echo "\nIterating: $iteratePath (SELF_FIRST)\n";
$iterator_self = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($iteratePath, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS),
    RecursiveIteratorIterator::SELF_FIRST
);

$actual_items_self = [];
foreach ($iterator_self as $item) {
    $fullPath = str_replace('\\', '/', $item->getPathname());
    $relativePathOrSelf = $fullPath;
    if (strpos($fullPath, $iteratePath . '/') === 0) {
        $relativePathOrSelf = str_replace($iteratePath . '/', '', $fullPath);
    } elseif ($fullPath === $iteratePath) {
        $relativePathOrSelf = '.'; // Represent the directory itself
    }

    $actual_items_self[] = sprintf("Path: %s, Type: %s, isDir: %d, isFile: %d",
        $relativePathOrSelf,
        $item->getType(),
        $item->isDir(),
        $item->isFile()
    );
}
// Do not sort $actual_items_self to preserve SELF_FIRST order for the parent
// but sort children to make EXPECTF stable for them.
$self_entry_output = '';
$children_output_self = [];
if (!empty($actual_items_self)) {
    $self_entry_output = array_shift($actual_items_self) . "\n"; // First entry (should be self)
    sort($actual_items_self); // Sort remaining children
    foreach($actual_items_self as $line) {
        $children_output_self[] = $line . "\n";
    }
}
echo $self_entry_output;
foreach($children_output_self as $line) {
    echo $line;
}

?>
--EXPECTF--
Iterating: %s/instance/mixtype_test (LEAVES_ONLY)
Path: instance_dir/.gitkeep, Type: file, isDir: 0, isFile: 1
Path: instance_file.txt, Type: file, isDir: 0, isFile: 1
Path: shared_dir/another_instance_file_in_shared_dir.txt, Type: file, isDir: 0, isFile: 1
Path: shared_dir/shared_dir_instance_file.txt, Type: file, isDir: 0, isFile: 1
Path: shared_dir/shared_dir_template_file.txt, Type: file, isDir: 0, isFile: 1
Path: shared_file.txt, Type: file, isDir: 0, isFile: 1
Path: template_dir/.gitkeep, Type: file, isDir: 0, isFile: 1
Path: template_file.txt, Type: file, isDir: 0, isFile: 1

Iterating: %s/instance/mixtype_test (SELF_FIRST)
Path: ., Type: dir, isDir: 1, isFile: 0
Path: instance_dir, Type: dir, isDir: 1, isFile: 0
Path: instance_dir/.gitkeep, Type: file, isDir: 0, isFile: 1
Path: instance_file.txt, Type: file, isDir: 0, isFile: 1
Path: shared_dir, Type: dir, isDir: 1, isFile: 0
Path: shared_dir/another_instance_file_in_shared_dir.txt, Type: file, isDir: 0, isFile: 1
Path: shared_dir/shared_dir_instance_file.txt, Type: file, isDir: 0, isFile: 1
Path: shared_dir/shared_dir_template_file.txt, Type: file, isDir: 0, isFile: 1
Path: shared_file.txt, Type: file, isDir: 0, isFile: 1
Path: template_dir, Type: dir, isDir: 1, isFile: 0
Path: template_dir/.gitkeep, Type: file, isDir: 0, isFile: 1
Path: template_file.txt, Type: file, isDir: 0, isFile: 1
