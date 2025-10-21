<?php
declare(strict_types=1);

/**
 * Application configuration for the Dubrovnik Coast admin portal.
 */

// Database credentials.
const DB_HOST = 'localhost';
const DB_NAME = 'ducoastcom_pm';
const DB_USER = 'ducoastcom_pmadmin';
const DB_PASSWORD = 'HH458L5pPMn789u';

// Hard-coded admin password hash (default: ChangeMe123!).
// Generate a new one with: php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
const ADMIN_PASSWORD_HASH = '$2y$10$fM2PZVxvbnyx6RShpsSrp.jj1s3MyiecP.89O6Ik.DR/VH53jJl1W';

// Session configuration.
const ADMIN_SESSION_KEY = 'dc_admin_authenticated';
const FLASH_SESSION_KEY = 'dc_admin_flash';

// Filesystem locations.
define('PROJECT_ROOT', dirname(__DIR__, 2));
define('PUBLIC_IMG_DIR', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'img');
define('PUBLIC_IMG_PREFIX', '/img');
define('PROPERTY_OUTPUT_DIR', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'properties');

// Public site configuration.
const SITE_BASE_URL = 'https://dubrovnik-coast.com';
