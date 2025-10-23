<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/booking.php';

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!class_exists(\Stripe\Webhook::class)) {
    $autoload = PROJECT_ROOT . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }
}

if (!class_exists(\Stripe\Webhook::class)) {
    http_response_code(500);
    echo 'Stripe SDK missing';
    exit;
}

try {
    if (!STRIPE_WEBHOOK_SECRET || STRIPE_WEBHOOK_SECRET === 'whsec_placeholder') {
        $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    } else {
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            STRIPE_WEBHOOK_SECRET
        );
        $event = $event->jsonSerialize();
    }
} catch (Throwable $exception) {
    http_response_code(400);
    echo 'Invalid payload';
    exit;
}

$type = $event['type'] ?? '';
$data = $event['data']['object'] ?? [];

try {
    $pdo = get_pdo();

    if ($type === 'checkout.session.completed') {
        $sessionId = $data['id'] ?? '';
        if ($sessionId === '') {
            throw new RuntimeException('Missing session id.');
        }

        $stmt = $pdo->prepare('SELECT booking_id FROM stripe_payments WHERE session_id = :session LIMIT 1');
        $stmt->execute([':session' => $sessionId]);
        $bookingId = $stmt->fetchColumn();
        if ($bookingId) {
            $stmtUpdate = $pdo->prepare(
                'UPDATE stripe_payments SET status = :status, payment_intent = :intent, payload = :payload
                 WHERE session_id = :session'
            );
            $stmtUpdate->execute([
                ':status' => 'succeeded',
                ':intent' => $data['payment_intent'] ?? null,
                ':payload' => json_encode($event, JSON_THROW_ON_ERROR),
                ':session' => $sessionId,
            ]);

            update_booking_status($pdo, (int) $bookingId, 'confirmed', 'webhook', 'stripe', [
                'session_id' => $sessionId,
            ]);
            send_booking_email($pdo, (int) $bookingId, 'confirmed');
        }
    } elseif ($type === 'checkout.session.expired') {
        $sessionId = $data['id'] ?? '';
        if ($sessionId !== '') {
            $stmt = $pdo->prepare('SELECT booking_id FROM stripe_payments WHERE session_id = :session LIMIT 1');
            $stmt->execute([':session' => $sessionId]);
            $bookingId = $stmt->fetchColumn();
            if ($bookingId) {
                $stmtUpdate = $pdo->prepare(
                    'UPDATE stripe_payments SET status = :status, payload = :payload WHERE session_id = :session'
                );
                $stmtUpdate->execute([
                    ':status' => 'cancelled',
                    ':payload' => json_encode($event, JSON_THROW_ON_ERROR),
                    ':session' => $sessionId,
                ]);
                update_booking_status($pdo, (int) $bookingId, 'expired', 'webhook', 'stripe', [
                    'session_id' => $sessionId,
                ]);
            }
        }
    } elseif ($type === 'payment_intent.payment_failed') {
        $intentId = $data['id'] ?? '';
        if ($intentId !== '') {
            $stmt = $pdo->prepare('SELECT booking_id FROM stripe_payments WHERE payment_intent = :intent LIMIT 1');
            $stmt->execute([':intent' => $intentId]);
            $bookingId = $stmt->fetchColumn();
            if ($bookingId) {
                update_booking_status($pdo, (int) $bookingId, 'pending_payment', 'webhook', 'stripe', [
                    'reason' => $data['last_payment_error']['message'] ?? null,
                ]);
            }
        }
    }

    http_response_code(200);
    echo 'OK';
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Webhook error';
}
