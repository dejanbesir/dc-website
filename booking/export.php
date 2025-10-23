<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/booking.php';

$propertyId = isset($_GET['property']) ? (int) $_GET['property'] : 0;
$token = isset($_GET['token']) ? trim((string) $_GET['token']) : '';

if ($propertyId <= 0 || $token === '') {
    http_response_code(400);
    echo 'Invalid request.';
    exit;
}

$pdo = get_pdo();
$calendar = ensure_property_calendar($pdo, $propertyId);

if (!$calendar || !hash_equals($calendar['export_token'], $token)) {
    http_response_code(403);
    echo 'Unauthorized.';
    exit;
}

$ics = render_property_ics($pdo, $propertyId);

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="availability.ics"');
echo $ics;
