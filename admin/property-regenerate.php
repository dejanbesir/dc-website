<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/generator.php';

require_admin();

$propertyId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($propertyId <= 0) {
    set_flash('error', 'Invalid property identifier.');
    header('Location: /admin/properties.php');
    exit;
}

$property = fetch_property($propertyId);
if (!$property) {
    set_flash('error', 'Property not found.');
    header('Location: /admin/properties.php');
    exit;
}

try {
    generate_property_page($propertyId);
    set_flash('success', sprintf('Static page for "%s" rebuilt.', $property['name']));
} catch (Throwable $exception) {
    set_flash('error', 'Failed to regenerate static page: ' . $exception->getMessage());
}

header('Location: /admin/properties.php');
exit;
