--TEST--
Check RecursiveDirectoryIterator with shadow directories
--SKIPIF--
<?php if (!extension_loaded('shadow')) {
    print 'skip';
} ?>
--FILE--
<?php
require_once 'recursive_iterator_setup.inc'; // Defines $template, $instance

// 1. Define directory structures
$template_files = [
    'template_only_file.txt' => 'content',
    'common_file.txt' => 'template content',
    'subdir1/template_sub_file.txt' => 'content',
    'common_dir/template_in_common_dir.txt' => 'content',
];
$instance_files = [
    'instance_only_file.txt' => 'content',
    'common_file.txt' => 'instance content', // Override
    'subdir2/instance_sub_file.txt' => 'content',
    'common_dir/instance_in_common_dir.txt' => 'content',
];

// Clean up previous runs if any
if (is_dir($template)) {
    shell_exec("rm -rf " . escapeshellarg($template));
}
if (is_dir($instance)) {
    shell_exec("rm -rf " . escapeshellarg($instance));
}

// Create directories and files
mkdir($template . '/subdir1', 0777, true);
mkdir($template . '/common_dir', 0777, true);
foreach ($template_files as $path => $content) {
    file_put_contents($template . '/' . $path, $content);
}

mkdir($instance . '/subdir2', 0777, true);
mkdir($instance . '/common_dir', 0777, true);
foreach ($instance_files as $path => $content) {
    file_put_contents($instance . '/' . $path, $content);
}

// Create an empty directory in template to test iteration over it
mkdir($template . '/empty_template_dir', 0777, true);
// Create an empty directory in instance to test iteration over it
mkdir($instance . '/empty_instance_dir', 0777, true);


// 2. Activate shadow
shadow($template, $instance);

// 3. Use RecursiveDirectoryIterator
$path_to_iterate = $template; // Iterate from the template path

echo "Iterating path: $path_to_iterate\n";

$iterator = new RecursiveDirectoryIterator(
    $path_to_iterate,
    RecursiveDirectoryIterator::SKIP_DOTS
);
$recursiveIterator = new RecursiveIteratorIterator(
    $iterator,
    RecursiveIteratorIterator::SELF_FIRST // List directories themselves, then their children
);

$results = [];
foreach ($recursiveIterator as $fileinfo) {
    $type = $fileinfo->isDir() ? 'dir' : 'file';
    // Normalize path for comparison
    $fullPath = $fileinfo->getPathname();
    $relativePath = str_replace($path_to_iterate . DIRECTORY_SEPARATOR, '', $fullPath);
    // Handle base path itself (if iterator returns it)
    if ($relativePath === $path_to_iterate) {
        $relativePath = basename($path_to_iterate);
    }
    $results[$relativePath] = $type;
}
ksort($results);

// 4. Assertions
$expected = [
    'common_dir' => 'dir',
    'common_dir/instance_in_common_dir.txt' => 'file', // From instance
    'common_dir/template_in_common_dir.txt' => 'file', // From template
    'common_file.txt' => 'file', // Overridden by instance
    'empty_instance_dir' => 'dir', // From instance
    'empty_template_dir' => 'dir', // From template
    'instance_only_file.txt' => 'file', // From instance
    'subdir1' => 'dir', // From template
    'subdir1/template_sub_file.txt' => 'file', // From template
    'subdir2' => 'dir', // From instance
    'subdir2/instance_sub_file.txt' => 'file', // From instance
    'template_only_file.txt' => 'file', // From template
];
ksort($expected);

echo "Expected:\n";
print_r($expected);
echo "Actual:\n";
print_r($results);

if ($results == $expected) {
    echo "TEST PASSED\n";
} else {
    echo "TEST FAILED\N";
    echo "Diff:\n";
    print_r(array_diff_assoc($expected, $results)); // Show what's missing or different in actual
    print_r(array_diff_assoc($results, $expected)); // Show what's extra in actual
}

// Clean up
shadow("",""); // disable shadowing
chdir(__DIR__); // Change out of the temp dirs
if (is_dir($template)) {
    shell_exec("rm -rf " . escapeshellarg($template));
}
if (is_dir($instance)) {
    shell_exec("rm -rf " . escapeshellarg($instance));
}

?>
--EXPECTF--
Iterating path: %s/template
Expected:
Array
(
    [common_dir] => dir
    [common_dir/instance_in_common_dir.txt] => file
    [common_dir/template_in_common_dir.txt] => file
    [common_file.txt] => file
    [empty_instance_dir] => dir
    [empty_template_dir] => dir
    [instance_only_file.txt] => file
    [subdir1] => dir
    [subdir1/template_sub_file.txt] => file
    [subdir2] => dir
    [subdir2/instance_sub_file.txt] => file
    [template_only_file.txt] => file
)
Actual:
Array
(
%A%
)
%S%
