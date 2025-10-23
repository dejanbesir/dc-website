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
define('BLOG_IMAGE_DIR', PUBLIC_IMG_DIR . DIRECTORY_SEPARATOR . 'blog');
define('BLOG_IMAGE_PREFIX', PUBLIC_IMG_PREFIX . '/blog');
define('BOOKING_EXPORT_DIR', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'ics');

// Public site configuration.
const SITE_BASE_URL = 'https://dubrovnik-coast.com';

// Stripe configuration (set real keys via environment override).
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: 'pk_test_placeholder');
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: 'sk_test_placeholder');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_placeholder');

// Booking email configuration.
define('BOOKING_FROM_EMAIL', getenv('BOOKING_FROM_EMAIL') ?: 'reservations@dubrovnik-coast.com');
define('BOOKING_FROM_NAME', getenv('BOOKING_FROM_NAME') ?: 'Dubrovnik Coast Reservations');
