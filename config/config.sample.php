<?php
/**
 * Application configuration — TEMPLATE
 * -----------------------------------
 * SETUP: copy this file to `config/config.php` and fill in the database
 * credentials for your server. `config/config.php` is intentionally NOT
 * committed to git so real passwords never end up in the repository.
 *
 *   cp config/config.sample.php config/config.php
 *
 * (InfinityFree shows these values in the "MySQL Databases" section of the panel.)
 */

// ----- Database -----
define('DB_HOST', 'localhost');      // InfinityFree example: sqlXXX.infinityfree.com
define('DB_NAME', 'ncd_printing');   // InfinityFree example: epiz_XXXXXXX_ncd_printing
define('DB_USER', 'root');           // InfinityFree example: epiz_XXXXXXX
define('DB_PASS', '');               // your database password
define('DB_CHARSET', 'utf8mb4');

// ----- Application -----
define('APP_NAME', 'NCD 3D Print');
define('APP_FULL_NAME', 'National Center for the Distinguished');
define('APP_TAGLINE', '3D Printing Requests');

// Prefix used for auto-generated request transaction numbers,
// e.g. TRANSACTION_PREFIX-2026-0007  ->  NCD-2026-0007
define('TRANSACTION_PREFIX', 'NCD');

// ----- Uploads -----
// Maximum size for a SINGLE uploaded file, in megabytes.
// Note: the real limit is also capped by your host's php.ini
// (upload_max_filesize / post_max_size). InfinityFree default is ~10 MB.
define('MAX_UPLOAD_MB', 10);
define('MAX_UPLOAD_SIZE', MAX_UPLOAD_MB * 1024 * 1024);

// Allowed extensions for the 3D project files.
define('ALLOWED_FILE_EXT', ['stl', '3mf', 'zip']);
// Allowed extensions for the single project image.
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// Physical upload directories (created automatically if missing).
define('UPLOAD_DIR',  __DIR__ . '/../uploads');
define('FILES_DIR',   UPLOAD_DIR . '/files');
define('IMAGES_DIR',  UPLOAD_DIR . '/images');

// ----- Filament warning thresholds (grams) -----
define('FILAMENT_WARN_LOW', 300);   // orange warning below this
define('FILAMENT_WARN_CRIT', 100);  // red warning below this

// ----- Timezone -----
date_default_timezone_set('Asia/Damascus');

// ----- Error reporting -----
// Set to false on a live/production server.
define('APP_DEBUG', true);
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
