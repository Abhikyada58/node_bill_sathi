<?php
/**
 * Environment Loader
 * Reads the .env file and loads all variables into PHP environment.
 * Call this once at the top of your entry point or config files.
 */

function loadEnv(string $path): void {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comment lines
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        // Only process lines with an = sign
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Remove surrounding quotes if any
        $value = trim($value, '"\'');

        if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Auto-load .env from project root
loadEnv(__DIR__ . '/../.env');
