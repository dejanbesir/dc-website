<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/generator.php';

require_admin();

$results = regenerate_all_properties();

$errors = array_filter($results, static fn ($result) => $result['status'] !== 'ok');

if (empty($results)) {
    set_flash('info', 'No properties found to regenerate.');
} elseif (empty($errors)) {
    set_flash('success', sprintf('Regenerated %d property pages.', count($results)));
} else {
    $messages = array_map(static function ($result) {
        return sprintf('%s (ID %d): %s', $result['slug'], $result['id'], $result['error'] ?? 'Unknown error');
    }, $errors);
    set_flash('error', 'Some pages failed to regenerate: ' . implode('; ', $messages));
}

header('Location: /admin/properties.php');
exit;
