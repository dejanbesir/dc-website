<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/booking.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    return;
}

try {
    $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload.']);
    return;
}

try {
    $reference = trim((string) ($input['reference'] ?? ''));
    if ($reference === '') {
        throw new InvalidArgumentException('Missing booking reference.');
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id FROM bookings WHERE reference = :reference LIMIT 1');
    $stmt->execute([':reference' => $reference]);
    $bookingId = $stmt->fetchColumn();
    if (!$bookingId) {
        throw new RuntimeException('Booking not found.');
    }

    $successUrl = SITE_BASE_URL . '/booking/success.php';
    $cancelUrl = SITE_BASE_URL . '/booking/checkout.php?reference=' . urlencode($reference) . '&cancelled=1';

    $session = api_create_stripe_session((int) $bookingId, $successUrl, $cancelUrl);

    echo json_encode([
        'success' => true,
        'session' => $session,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}
