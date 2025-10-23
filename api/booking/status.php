<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/booking.php';

header('Content-Type: application/json');

try {
    $reference = isset($_GET['reference']) ? trim((string) $_GET['reference']) : '';
    if ($reference === '') {
        throw new InvalidArgumentException('Missing reference.');
    }

    $status = api_get_booking_status($reference);

    echo json_encode([
        'success' => true,
        'status' => $status,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}
