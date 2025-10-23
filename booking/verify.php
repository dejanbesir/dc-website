<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/booking.php';

$message = '';
$error = '';
$reference = null;

$token = isset($_GET['token']) ? trim((string) $_GET['token']) : '';
if ($token === '') {
    $error = 'Missing verification token.';
} else {
    try {
        $booking = api_verify_booking_email($token);
        $reference = $booking['reference'] ?? null;
        $message = 'Email verified. Your dates are being held while you complete payment.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Verification &mdash; Dubrovnik Coast</title>
    <link rel="stylesheet" href="https://cdn.tailwindcss.com">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 flex items-center justify-center px-4">
    <div class="max-w-xl w-full bg-white rounded-xl shadow-lg p-8 space-y-5">
        <h1 class="text-2xl font-semibold text-indigo-800">Booking Verification</h1>

        <?php if ($message !== ''): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($reference && $error === ''): ?>
            <div class="space-y-3">
                <p>Your booking reference is <span class="font-semibold"><?= htmlspecialchars($reference) ?></span>.</p>
                <p class="text-sm text-slate-600">
                    Please continue to payment to finalise your reservation. We will send a confirmation email once payment is complete.
                </p>
                <a
                    href="/booking/checkout.php?reference=<?= urlencode($reference) ?>"
                    class="inline-flex items-center gap-2 px-5 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                >
                    Continue to secure payment
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        <?php endif; ?>

        <div class="text-sm text-slate-500">
            <a href="/properties/" class="text-indigo-600 hover:underline">Browse more villas</a>
            &middot;
            <a href="/contact/" class="text-indigo-600 hover:underline">Contact our concierge</a>
        </div>
    </div>
</body>
</html>
