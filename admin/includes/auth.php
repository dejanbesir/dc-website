<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Ensure sessions are available for auth + flash handling.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Returns true when the admin is authenticated.
 */
function is_admin_authenticated(): bool
{
    return !empty($_SESSION[ADMIN_SESSION_KEY]);
}

/**
 * Marks the administrator as authenticated.
 */
function mark_admin_authenticated(): void
{
    $_SESSION[ADMIN_SESSION_KEY] = true;
}

/**
 * Logs the admin out and clears session state.
 */
function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Redirects to login when not authenticated.
 */
function require_admin(): void
{
    if (is_admin_authenticated()) {
        return;
    }

    header('Location: /admin/index.php');
    exit;
}

/**
 * Stores a flash message.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION[FLASH_SESSION_KEY][$type][] = $message;
}

/**
 * Returns and clears flash messages.
 *
 * @return array{success?: string[], error?: string[], info?: string[]}
 */
function get_flash_messages(): array
{
    $messages = $_SESSION[FLASH_SESSION_KEY] ?? [];
    unset($_SESSION[FLASH_SESSION_KEY]);

    return $messages;
}
