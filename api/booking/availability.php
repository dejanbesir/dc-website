<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/booking.php';

header('Content-Type: application/json');

try {
    $propertyId = isset($_GET['property']) ? (int) $_GET['property'] : 0;
    if ($propertyId <= 0) {
        throw new InvalidArgumentException('Missing property.');
    }

    $start = $_GET['start'] ?? (new DateTimeImmutable('first day of this month'))->format('Y-m-01');
    $end = $_GET['end'] ?? (new DateTimeImmutable('+6 months'))->format('Y-m-01');

    $availability = api_fetch_availability($propertyId, $start, $end);

    echo json_encode([
        'success' => true,
        'data' => $availability,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}
