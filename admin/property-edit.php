<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$propertyId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($propertyId <= 0) {
    set_flash('error', 'Property not found.');
    header('Location: /admin/properties.php');
    exit;
}

$property = fetch_property($propertyId);
if (!$property) {
    set_flash('error', 'Property not found or already removed.');
    header('Location: /admin/properties.php');
    exit;
}

$isEdit = true;

require __DIR__ . '/property-form.php';
