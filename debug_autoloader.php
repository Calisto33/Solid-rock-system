<?php
// Create this file in: C:\xampp\htdocs\Solid-rock-system\debug_autoloader.php

echo "<h2>Autoloader Debug Information</h2>";

// Check current directory
echo "<b>Current directory:</b> " . __DIR__ . "<br>";
echo "<b>Current working directory:</b> " . getcwd() . "<br><br>";

// Check various autoloader paths
$autoloader_paths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    './vendor/autoload.php',
    '../vendor/autoload.php',
    'vendor/autoload.php'
];

echo "<b>Checking autoloader paths:</b><br>";
foreach ($autoloader_paths as $path) {
    $exists = file_exists($path);
    echo "Path: {$path} - " . ($exists ? "<span style='color:green'>EXISTS</span>" : "<span style='color:red'>NOT FOUND</span>") . "<br>";
    
    if ($exists) {
        echo "  → Attempting to include...<br>";
        try {
            require_once $path;
            echo "  → <span style='color:green'>SUCCESS: Autoloader included</span><br>";
            break;
        } catch (Exception $e) {
            echo "  → <span style='color:red'>ERROR: " . $e->getMessage() . "</span><br>";
        }
    }
}

echo "<br>";

// Check if PHPMailer classes exist
$classes_to_check = [
    'PHPMailer\\PHPMailer\\PHPMailer',
    'PHPMailer\\PHPMailer\\SMTP',
    'PHPMailer\\PHPMailer\\Exception'
];

echo "<b>Checking PHPMailer classes:</b><br>";
foreach ($classes_to_check as $class) {
    $exists = class_exists($class);
    echo "Class: {$class} - " . ($exists ? "<span style='color:green'>EXISTS</span>" : "<span style='color:red'>NOT FOUND</span>") . "<br>";
}

echo "<br>";

// Check vendor directory contents
$vendor_path = __DIR__ . '/vendor';
if (is_dir($vendor_path)) {
    echo "<b>Contents of vendor directory:</b><br>";
    $vendor_contents = scandir($vendor_path);
    foreach ($vendor_contents as $item) {
        if ($item !== '.' && $item !== '..') {
            echo "- {$item}<br>";
        }
    }
    
    // Check specifically for PHPMailer
    $phpmailer_path = $vendor_path . '/phpmailer';
    if (is_dir($phpmailer_path)) {
        echo "<br><b>PHPMailer directory found. Contents:</b><br>";
        $phpmailer_contents = scandir($phpmailer_path);
        foreach ($phpmailer_contents as $item) {
            if ($item !== '.' && $item !== '..') {
                echo "- {$item}<br>";
            }
        }
    } else {
        echo "<br><span style='color:red'>PHPMailer directory not found in vendor</span><br>";
    }
} else {
    echo "<span style='color:red'>Vendor directory not found</span><br>";
}

// Test creating PHPMailer instance
echo "<br><b>Testing PHPMailer instantiation:</b><br>";
try {
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        echo "<span style='color:green'>SUCCESS: PHPMailer instance created</span><br>";
        echo "PHPMailer version: " . $mail::VERSION . "<br>";
    } else {
        echo "<span style='color:red'>PHPMailer class not found</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>ERROR creating PHPMailer: " . $e->getMessage() . "</span><br>";
}

// Show PHP include path
echo "<br><b>PHP Include Path:</b><br>";
echo ini_get('include_path') . "<br>";

// Show loaded extensions
echo "<br><b>Relevant PHP Extensions:</b><br>";
$extensions = ['curl', 'openssl', 'mbstring'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "Extension {$ext}: " . ($loaded ? "<span style='color:green'>LOADED</span>" : "<span style='color:red'>NOT LOADED</span>") . "<br>";
}
?>