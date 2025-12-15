--TEST--
Check iterators failure
--SKIPIF--
<?php if (!extension_loaded("shadow")) print "skip"; ?>
--FILE--
<?php
require_once('setup.inc');

$topLevelDirs = ["$template/qwe", "$template/api",];

foreach ($topLevelDirs as $dir) {
//    var_dump($dir);
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $count++;
        // Add a var_dump to see what items are being iterated
        // var_dump($item->getPathname());
    }
    // Add a var_dump to see the final count for each directory
    // var_dump("Count for $dir: $count");
}

// Add a success message if no errors occur
echo "Iterator test completed successfully.\n";

?>
--EXPECT--
Iterator test completed successfully.
