--TEST--
Test SPL RecursiveDirectoryIterator with mixed files and directories
--SKIPIF--
<?php if (!extension_loaded('shadow')) {
    print 'skip';
} ?>
--FILE--
<?php
require_once 'setup.inc';
chdir($instance);

/*
 * This test reproduces BR-12610: RecursiveDirectoryIterator fails
 * when Shadow treats a file as a directory.
 *
 * Setup:
 * - Directory structure has both files and subdirectories
 * - RecursiveDirectoryIterator should only recurse into directories
 * - Files should NOT trigger "Failed to open directory" errors
 */

echo "Testing RecursiveDirectoryIterator...\n";

try {
    $iterator = new RecursiveDirectoryIterator(
        '.',
        RecursiveDirectoryIterator::SKIP_DOTS
    );
    
    $count_dirs = 0;
    $count_files = 0;
    $dirs = [];
    $files = [];
    
    foreach ($iterator as $item) {
        $name = $item->getFilename();
        
        if ($item->isDir()) {
            $count_dirs++;
            $dirs[] = $name;
            
            // Critical test: hasChildren() must return correct value
            if (!$iterator->hasChildren()) {
                echo "ERROR: Directory reported as NOT having children!\n";
                exit(1);
            }
        } else if ($item->isFile()) {
            $count_files++;
            $files[] = $name;
            
            // Critical test: files should NOT have children
            if ($iterator->hasChildren()) {
                echo "ERROR: File '{$name}' reported as having children!\n";
                exit(1);
            }
        }
    }
    
    // Sort for consistent output
    sort($dirs);
    sort($files);
    
    echo "Found directories: " . implode(', ', $dirs) . "\n";
    echo "Found files: " . implode(', ', $files) . "\n";
    echo "Summary: $count_dirs directories, $count_files files\n";
    echo "SUCCESS: RecursiveDirectoryIterator works correctly\n";
    
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nTesting RecursiveIteratorIterator...\n";

try {
    $directory = new RecursiveDirectoryIterator(
        '.',
        RecursiveDirectoryIterator::SKIP_DOTS
    );
    
    $iterator = new RecursiveIteratorIterator(
        $directory,
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $errors = [];
    foreach ($iterator as $item) {
        $path = $item->getPathname();
        
        // The critical test: verify files are never treated as directories
        // This was the bug - files would be opened as directories causing crashes
        if ($item->isFile()) {
            // Files are OK - they should never trigger directory operations
        } else if ($item->isDir()) {
            // Directories are OK
        } else {
            $errors[] = "Unknown file type for $path";
        }
    }
    
    if (empty($errors)) {
        echo "SUCCESS: No errors during recursive iteration\n";
    } else {
        foreach ($errors as $error) {
            echo "ERROR: $error\n";
        }
        exit(1);
    }
    
    echo "SUCCESS: RecursiveIteratorIterator works correctly\n";
    
} catch (UnexpectedValueException $e) {
    // This is the error we're fixing:
    // "Failed to open directory: operation failed"
    echo "FAIL: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

?>
--EXPECT--
Testing RecursiveDirectoryIterator...
Found directories: cache, custom, instdir, nowritedir, templdir, templdir2, txt
Found files: iinclude.php, instance_only.php, manifest.php, opcache-override-me.php, template_only.php, test.php, tinclude.php, unwritable.txt
Summary: 7 directories, 8 files
SUCCESS: RecursiveDirectoryIterator works correctly

Testing RecursiveIteratorIterator...
SUCCESS: No errors during recursive iteration
SUCCESS: RecursiveIteratorIterator works correctly

