<?php
/**
 * Paymongo Configuration (v2 app)
 * Loads API keys from environment variables or .env file (not committed to git)
 */

// Load .env file if it exists (project root of v2 app)
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');

            // Set environment variable if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Paymongo API Keys
// Priority: Environment variable > .env file > fallback to empty string (no secrets committed)
$paymongo_secret_key = getenv('PAYMONGO_SECRET_KEY') ?:
                       (isset($_ENV['PAYMONGO_SECRET_KEY']) ? $_ENV['PAYMONGO_SECRET_KEY'] : '');

$paymongo_public_key = getenv('PAYMONGO_PUBLIC_KEY') ?:
                      (isset($_ENV['PAYMONGO_PUBLIC_KEY']) ? $_ENV['PAYMONGO_PUBLIC_KEY'] : '');

$paymongo_webhook_secret = getenv('PAYMONGO_WEBHOOK_SECRET') ?:
                           (isset($_ENV['PAYMONGO_WEBHOOK_SECRET']) ? $_ENV['PAYMONGO_WEBHOOK_SECRET'] : '');

// Export as constants or return as array
define('PAYMONGO_SECRET_KEY', $paymongo_secret_key);
define('PAYMONGO_PUBLIC_KEY', $paymongo_public_key);
define('PAYMONGO_WEBHOOK_SECRET', $paymongo_webhook_secret);

// Function to get Paymongo config
function getPaymongoConfig() {
    return [
        'secret_key' => PAYMONGO_SECRET_KEY,
        'public_key' => PAYMONGO_PUBLIC_KEY,
        'webhook_secret' => PAYMONGO_WEBHOOK_SECRET,
        'api_url' => 'https://api.paymongo.com/v1'
    ];
}


