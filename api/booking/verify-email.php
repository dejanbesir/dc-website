<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/booking.php';

header('Content-Type: application/json');

try {
    $token = isset($_GET['token']) ? trim((string) $_GET['token']) : '';
    if ($token === '') {
        throw new InvalidArgumentException('Missing token.');
    }

    $booking = api_verify_booking_email($token);

    echo json_encode([
        'success' => true,
        'booking' => [
            'id' => $booking['id'],
            'reference' => $booking['reference'],
            'status' => $booking['status'],
        ],
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}
