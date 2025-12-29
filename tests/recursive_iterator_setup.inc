<?php
$temp_base = sys_get_temp_dir() ?: '/tmp';
$template = $temp_base . '/shadow_template_test_dir_' . uniqid();
$instance = $temp_base . '/shadow_instance_test_dir_' . uniqid();

// Function to recursively delete a directory
function deleteDirectory($dir) {
    if (!is_dir($dir) || strpos(realpath($dir), sys_get_temp_dir()) !== 0) {
        // Only delete if it's a directory and within the temp path for safety
        error_log("Skipping delete for non-dir or non-temp path: $dir");
        return;
    }
    $items = array_diff(scandir($dir), array('.', '..'));
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

// Clean up directories from previous potential runs (e.g. if script aborted)
// More specific cleanup is done by the test file itself, this is a safeguard.
// deleteDirectory($template); // These will be unique now, so less critical to pre-delete
// deleteDirectory($instance);

// Create fresh directories
if (!mkdir($template, 0777, true)) {
    die("Failed to create template directory: $template\n");
}
if (!mkdir($instance, 0777, true)) {
    // Clean up template dir if instance dir creation fails
    deleteDirectory($template);
    die("Failed to create instance directory: $instance\n");
}

// Ensure shadow extension is loaded (though test has SKIP_IF, good for standalone setup)
if (!extension_loaded('shadow')) {
    $prefix = (PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '';
    if (!@dl($prefix . 'shadow.' . PHP_SHLIB_SUFFIX) && !@dl('shadow.' . PHP_SHLIB_SUFFIX)) {
        // Silently fail if dl is not possible, SKIP_IF in test will handle it
    }
}

// The main test file (recursive_iterator.phpt) will handle cleanup.
// register_shutdown_function is an option but explicit cleanup in test is often clearer.
?>
