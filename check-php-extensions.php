<?php
/**
 * PHP Extensions Checker for Laravel Church Media Platform
 * Upload this file to your hosting.com server and run it in browser or CLI
 */

echo "=== Laravel Church Media Platform - PHP Extensions Check ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Required Laravel 12 Extensions
$required_extensions = [
    'bcmath' => 'Arbitrary precision mathematics',
    'ctype' => 'Character type checking',
    'curl' => 'Client URL library for HTTP requests',
    'dom' => 'Document Object Model manipulation',
    'fileinfo' => 'File information functions',
    'json' => 'JavaScript Object Notation support',
    'mbstring' => 'Multi-byte string handling',
    'openssl' => 'Secure Sockets Layer functions',
    'pcre' => 'Perl Compatible Regular Expressions',
    'pdo' => 'PHP Data Objects for database access',
    'tokenizer' => 'Script tokenizing functions',
    'xml' => 'XML parsing support',
    'zip' => 'Archive handling'
];

// Church Platform Specific Extensions
$church_platform_extensions = [
    'pdo_pgsql' => 'PostgreSQL database connection',
    'gd' => 'Graphics library for image manipulation',
    'imagick' => 'ImageMagick for advanced image processing',
    'intl' => 'Internationalization functions',
    'exif' => 'Image metadata reading'
];

// Performance/Optional Extensions
$performance_extensions = [
    'opcache' => 'Opcode caching for performance',
    'redis' => 'Redis caching support',
    'memcached' => 'Memcached caching support'
];

// Check extensions function
function checkExtensions($extensions, $category) {
    echo "=== $category ===\n";
    $missing = [];
    
    foreach ($extensions as $ext => $description) {
        $loaded = extension_loaded($ext);
        $status = $loaded ? "✅ LOADED" : "❌ MISSING";
        echo sprintf("%-15s %s - %s\n", $ext, $status, $description);
        
        if (!$loaded) {
            $missing[] = $ext;
        }
    }
    
    echo "\n";
    return $missing;
}

// Run checks
$missing_required = checkExtensions($required_extensions, "REQUIRED LARAVEL 12 EXTENSIONS");
$missing_platform = checkExtensions($church_platform_extensions, "CHURCH PLATFORM EXTENSIONS");
$missing_performance = checkExtensions($performance_extensions, "PERFORMANCE EXTENSIONS (Optional)");

// Summary
echo "=== SUMMARY ===\n";

if (empty($missing_required)) {
    echo "✅ All required Laravel extensions are loaded!\n";
} else {
    echo "❌ Missing required extensions: " . implode(', ', $missing_required) . "\n";
}

if (empty($missing_platform)) {
    echo "✅ All church platform extensions are loaded!\n";
} else {
    echo "⚠️  Missing platform extensions: " . implode(', ', $missing_platform) . "\n";
}

if (!empty($missing_performance)) {
    echo "ℹ️  Missing optional performance extensions: " . implode(', ', $missing_performance) . "\n";
}

// PHP Configuration Check
echo "\n=== PHP CONFIGURATION ===\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "s\n";
echo "Max Input Vars: " . ini_get('max_input_vars') . "\n";

// Database PDO Drivers Check
echo "\n=== AVAILABLE PDO DRIVERS ===\n";
if (extension_loaded('pdo')) {
    $drivers = PDO::getAvailableDrivers();
    foreach ($drivers as $driver) {
        echo "✅ PDO driver: $driver\n";
    }
    
    if (in_array('pgsql', $drivers)) {
        echo "✅ PostgreSQL PDO driver found!\n";
    } else {
        echo "❌ PostgreSQL PDO driver missing!\n";
    }
} else {
    echo "❌ PDO extension not loaded!\n";
}

// Composer requirements check
echo "\n=== COMPOSER PLATFORM CHECK ===\n";
if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    echo "✅ PHP version compatible with Laravel 12 (>= 8.2)\n";
} else {
    echo "❌ PHP version too old for Laravel 12 (requires >= 8.2)\n";
}

// Instructions for hosting.com
echo "\n=== HOSTING.COM INSTRUCTIONS ===\n";
echo "If extensions are missing:\n";
echo "1. Log into your hosting.com cPanel\n";
echo "2. Go to 'Software' section → 'Select PHP Version'\n";
echo "3. Select PHP 8.4 (or your desired version)\n";
echo "4. Click 'Extensions' tab\n";
echo "5. Enable missing extensions by checking the boxes\n";
echo "6. Click 'Save' to apply changes\n";
echo "7. If extensions are not available, contact hosting.com support\n\n";

echo "For reseller accounts, use WHM:\n";
echo "1. WHM → Software → MultiPHP INI Editor\n";
echo "2. Select domain and PHP version\n";
echo "3. Enable required extensions\n\n";

echo "=== END REPORT ===\n";
?>