<?php
/**
 * Vercel PHP Router for Bill_Sathi
 * This script catches all serverless requests and routes them to the correct PHP file in the root or auth folder.
 */

// Get the requested URI path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$projectRoot = dirname(__DIR__);

// Route root to index.php
if ($requestUri === '/' || $requestUri === '') {
    $requestUri = '/index.php';
}

$targetFile = $projectRoot . $requestUri;

// If a directory is requested, attempt to serve its index.php
if (is_dir($targetFile)) {
    $targetFile = rtrim($targetFile, '/') . '/index.php';
}
// Try appending .php for extensionless URLs (e.g. /dashboard -> /dashboard.php)
elseif (!file_exists($targetFile)) {
    if (file_exists($targetFile . '.php')) {
        $targetFile .= '.php';
    }
}

// Security constraints to block sensitive directories
$protectedDirs = ['/database', '/config', '/vendor'];
foreach ($protectedDirs as $dir) {
    if (strpos($requestUri, $dir) === 0) {
        http_response_code(403);
        die('403 Forbidden - Access to sensitive directories is blocked.');
    }
}

// Block access to .env files
if (strpos(basename($targetFile), '.env') === 0) {
    http_response_code(403);
    die('403 Forbidden');
}

// Serve the script if it exists and is a PHP file
if (file_exists($targetFile) && is_file($targetFile) && pathinfo($targetFile, PATHINFO_EXTENSION) === 'php') {
    // Override environment variables to emulate direct execution
    $_SERVER['SCRIPT_FILENAME'] = $targetFile;
    $_SERVER['SCRIPT_NAME'] = str_replace($projectRoot, '', $targetFile);
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    
    // Change current working directory to the directory of the target script
    // This ensures that all require_once(__DIR__ . '/...') calls in your existing files work correctly!
    chdir(dirname($targetFile));
    
    // Execute the target script
    require $targetFile;
} else {
    // If the file doesn't exist or isn't a PHP file, return a 404
    http_response_code(404);
    echo "404 Not Found";
}
