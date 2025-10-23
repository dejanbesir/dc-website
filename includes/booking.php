<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/bookings.php';
require_once __DIR__ . '/../admin/includes/config.php';

/**
 * Returns availability blocks for a property within a window.
 *
 * @return array<int, array<string,mixed>>
 */
function api_fetch_availability(int $propertyId, string $start, string $end): array
{
    $pdo = get_pdo();
    $blocks = fetch_calendar_blocks($pdo, $propertyId, $start, $end);

    return array_map(static function (array $block): array {
        return [
            'start' => $block['start_date'],
            'end' => $block['end_date'],
            'source' => $block['source'],
            'reference' => $block['reference'] ?? null,
        ];
    }, $blocks);
}

/**
 * Creates a pending booking and sends verification email.
 *
 * @param array<string,mixed> $payload
 * @param array<int, array<string,mixed>> $travellers
 */
function api_create_booking(array $payload, array $travellers): array
{
    $pdo = get_pdo();

    if (!is_property_range_available(
        $pdo,
        (int) $payload['property_id'],
        $payload['arrival_date'],
        $payload['departure_date']
    )) {
        throw new RuntimeException('Selected dates are no longer available.');
    }

    $result = create_pending_booking($pdo, $payload, $travellers);

    send_booking_email($pdo, (int) $result['booking_id'], 'verify');

    return $result;
}

/**
 * Verifies email token and moves booking to pending payment.
 */
function api_verify_booking_email(string $token): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE email_token = :token LIMIT 1');
    $stmt->execute([':token' => $token]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new RuntimeException('Invalid or expired token.');
    }

    $pdo->beginTransaction();
    try {
        $stmtUpdate = $pdo->prepare(
            'UPDATE bookings
             SET email_verified_at = NOW(), status = :status, email_token = NULL
             WHERE id = :id'
        );
        $stmtUpdate->execute([
            ':status' => 'pending_payment',
            ':id' => $booking['id'],
        ]);

        record_booking_event($pdo, (int) $booking['id'], 'guest', null, 'email_verified', null);

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    return fetch_booking($pdo, (int) $booking['id']) ?? [];
}

/**
 * Retrieves booking status and summary for polling.
 */
function api_get_booking_status(string $reference): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id FROM bookings WHERE reference = :reference LIMIT 1');
    $stmt->execute([':reference' => $reference]);
    $bookingId = $stmt->fetchColumn();
    if (!$bookingId) {
        throw new RuntimeException('Booking not found.');
    }

    $booking = fetch_booking($pdo, (int) $bookingId);
    if (!$booking) {
        throw new RuntimeException('Booking not found.');
    }

    return [
        'status' => $booking['status'],
        'reference' => $booking['reference'],
        'arrival_date' => $booking['arrival_date'],
        'departure_date' => $booking['departure_date'],
        'nights' => $booking['nights'],
        'total_amount' => $booking['total_amount'],
        'currency' => $booking['currency'],
    ];
}

/**
 * Creates a Stripe Checkout session for a booking.
 *
 * @return array{session_id:string,public_key:string}
 */
function api_create_stripe_session(int $bookingId, string $successUrl, string $cancelUrl): array
{
    $pdo = get_pdo();
    $booking = fetch_booking($pdo, $bookingId);
    if (!$booking) {
        throw new RuntimeException('Booking not found.');
    }

    if ($booking['status'] !== 'pending_payment' && $booking['status'] !== 'payment_processing') {
        throw new RuntimeException('Booking is not ready for payment.');
    }

    if (!class_exists(\Stripe\StripeClient::class)) {
        $autoloadPath = PROJECT_ROOT . '/vendor/autoload.php';
        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
        }
    }

    if (!class_exists(\Stripe\StripeClient::class)) {
        throw new RuntimeException('Stripe SDK not installed. Run "composer require stripe/stripe-php".');
    }

    $stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
    $lineItems = [[
        'quantity' => 1,
        'price_data' => [
            'currency' => strtolower($booking['currency']),
            'unit_amount' => (int) round($booking['total_amount'] * 100),
            'product_data' => [
                'name' => sprintf(
                    '%s stay %s - %s',
                    $booking['property_name'] ?? 'Property',
                    $booking['arrival_date'],
                    $booking['departure_date']
                ),
                'metadata' => [
                    'booking_reference' => $booking['reference'],
                    'property_id' => $booking['property_id'],
                ],
            ],
        ],
    ]];

    $session = $stripe->checkout->sessions->create([
        'mode' => 'payment',
        'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $cancelUrl,
        'line_items' => $lineItems,
        'metadata' => [
            'booking_id' => $bookingId,
            'reference' => $booking['reference'],
        ],
        'customer_email' => $booking['email'],
    ]);

    $stmt = $pdo->prepare(
        'UPDATE bookings SET status = :status, stripe_session_id = :session WHERE id = :id'
    );
    $stmt->execute([
        ':status' => 'payment_processing',
        ':session' => $session->id,
        ':id' => $bookingId,
    ]);

    $stmtPayment = $pdo->prepare(
        'INSERT INTO stripe_payments (booking_id, session_id, amount, currency)
         VALUES (:booking, :session, :amount, :currency)'
    );
    $stmtPayment->execute([
        ':booking' => $bookingId,
        ':session' => $session->id,
        ':amount' => $booking['total_amount'],
        ':currency' => $booking['currency'],
    ]);

    record_booking_event($pdo, $bookingId, 'guest', null, 'stripe_session_created', [
        'session_id' => $session->id,
    ]);

    return [
        'session_id' => $session->id,
        'public_key' => STRIPE_PUBLISHABLE_KEY,
    ];
}

/**
 * Sends booking emails.
 */
function send_booking_email(PDO $pdo, int $bookingId, string $type): void
{
    $booking = fetch_booking($pdo, $bookingId);
    if (!$booking || empty($booking['contact'])) {
        return;
    }

    $to = sprintf('"%s" <%s>', $booking['contact']['full_name'], $booking['email']);
    $subject = '';
    $body = '';

    switch ($type) {
        case 'verify':
            $subject = 'Confirm your email for booking ' . $booking['reference'];
            $verifyUrl = SITE_BASE_URL . '/booking/verify.php?token=' . urlencode($booking['email_token']);
            $body = <<<HTML
<p>Hi {$booking['contact']['full_name']},</p>
<p>Thank you for choosing Dubrovnik Coast. Please confirm your email address to continue with your booking.</p>
<p><a href="{$verifyUrl}" style="padding:10px 16px;background:#4f46e5;color:#ffffff;text-decoration:none;border-radius:6px;">Confirm email</a></p>
<p>If the button does not work, copy this link: {$verifyUrl}</p>
HTML;
            break;

        case 'confirmed':
            $subject = 'Booking confirmed - ' . $booking['reference'];
            $body = <<<HTML
<p>Hi {$booking['contact']['full_name']},</p>
<p>Your stay from {$booking['arrival_date']} to {$booking['departure_date']} is confirmed.</p>
<p>Total amount: {$booking['total_amount']} {$booking['currency']}</p>
<p>We look forward to welcoming you to Dubrovnik Coast.</p>
HTML;
            break;

        case 'cancelled':
            $subject = 'Booking cancelled - ' . $booking['reference'];
            $body = <<<HTML
<p>Hi {$booking['contact']['full_name']},</p>
<p>Your booking {$booking['reference']} has been cancelled. If this is unexpected, please contact us.</p>
HTML;
            break;

        default:
            return;
    }

    send_system_email($to, $subject, wrap_email_template($body));
}

/**
 * Simple mail sender.
 */
function send_system_email(string $to, string $subject, string $htmlBody): void
{
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $headers[] = 'From: ' . BOOKING_FROM_NAME . ' <' . BOOKING_FROM_EMAIL . '>';

    @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
}

/**
 * Wraps email body in a basic template.
 */
function wrap_email_template(string $content): string
{
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dubrovnik Coast</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:auto;background:#ffffff;border-radius:8px;">
    <tr>
      <td style="padding:24px;">
        <h2 style="color:#4f46e5;margin-top:0;">Dubrovnik Coast</h2>
        {$content}
        <p style="margin-top:24px;font-size:12px;color:#718096;">This email was sent by Dubrovnik Coast Reservations.</p>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}
