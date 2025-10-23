<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/booking.php';

$sessionId = isset($_GET['session_id']) ? trim((string) $_GET['session_id']) : '';
$booking = null;
$error = '';

if ($sessionId !== '') {
    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare(
            'SELECT b.* FROM bookings b
             JOIN stripe_payments sp ON sp.booking_id = b.id
             WHERE sp.session_id = :session LIMIT 1'
        );
        $stmt->execute([':session' => $sessionId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking) {
            $error = 'We could not locate your booking. Our team will follow up shortly.';
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
} else {
    $error = 'Missing Stripe session reference.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You &mdash; Dubrovnik Coast</title>
    <link rel="stylesheet" href="https://cdn.tailwindcss.com">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 flex items-center justify-center px-4">
    <div class="max-w-xl w-full bg-white rounded-xl shadow-lg p-8 space-y-5">
        <h1 class="text-2xl font-semibold text-indigo-800">Thank you!</h1>
        <?php if ($error !== ''): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif ($booking): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded">
                Your stay is confirmed. A detailed itinerary and receipt have been emailed to you.
            </div>
            <div class="text-sm text-slate-600 space-y-2">
                <p>Booking reference <span class="font-semibold text-slate-800"><?= htmlspecialchars($booking['reference']) ?></span></p>
                <p>Dates: <?= htmlspecialchars($booking['arrival_date']) ?> to <?= htmlspecialchars($booking['departure_date']) ?></p>
                <p>Total amount: <?= number_format((float) $booking['total_amount'], 2) ?> <?= htmlspecialchars($booking['currency']) ?></p>
            </div>
        <?php endif; ?>

        <div class="text-sm text-slate-500">
            <a href="/properties/" class="text-indigo-600 hover:underline">Explore more residences</a>
            &middot;
            <a href="/contact/" class="text-indigo-600 hover:underline">Speak with our concierge</a>
        </div>
    </div>
</body>
</html>
