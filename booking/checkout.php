<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/booking.php';

$reference = isset($_GET['reference']) ? trim((string) $_GET['reference']) : '';
$booking = null;
$error = '';

if ($reference !== '') {
    try {
        $status = api_get_booking_status($reference);
        $booking = $status;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
} else {
    $error = 'Booking reference missing.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment &mdash; Dubrovnik Coast</title>
    <link rel="stylesheet" href="https://cdn.tailwindcss.com">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 flex items-center justify-center px-4">
    <div class="max-w-xl w-full bg-white rounded-xl shadow-lg p-8 space-y-5">
        <h1 class="text-2xl font-semibold text-indigo-800">Secure payment</h1>

        <?php if ($error !== ''): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif ($booking): ?>
            <div class="space-y-3 text-sm text-slate-600">
                <p>Booking reference <span class="font-semibold text-slate-800"><?= htmlspecialchars($booking['reference']) ?></span></p>
                <p>
                    Dates: <?= htmlspecialchars($booking['arrival_date']) ?> to <?= htmlspecialchars($booking['departure_date']) ?>
                    (<?= (int) $booking['nights'] ?> nights)
                </p>
                <p>Total amount: <span class="font-semibold"><?= number_format((float) $booking['total_amount'], 2) ?> <?= htmlspecialchars($booking['currency']) ?></span></p>
                <p>Status: <span class="uppercase tracking-wide text-xs font-semibold"><?= htmlspecialchars($booking['status']) ?></span></p>
            </div>
            <?php if ($booking['status'] === 'confirmed'): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded">
                    Payment received. Thank you! You will receive a confirmation email shortly.
                </div>
            <?php else: ?>
                <button
                    type="button"
                    id="booking-pay-button"
                    class="w-full px-5 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-60"
                    data-reference="<?= htmlspecialchars($booking['reference']) ?>"
                >
                    Continue to secure payment
                </button>
                <p id="booking-pay-status" class="text-sm text-slate-500"></p>
            <?php endif; ?>
        <?php endif; ?>

        <div class="text-sm text-slate-500">
            <a href="/properties/" class="text-indigo-600 hover:underline">Back to properties</a>
            &middot;
            <a href="/contact/" class="text-indigo-600 hover:underline">Need assistance?</a>
        </div>
    </div>

    <script>
      (function() {
        const publicKey = "<?= addslashes(STRIPE_PUBLISHABLE_KEY) ?>";
        const payButton = document.getElementById('booking-pay-button');
        const statusEl = document.getElementById('booking-pay-status');
        if (!payButton || !publicKey || publicKey === 'pk_test_placeholder') return;

        const stripe = Stripe(publicKey);

        payButton.addEventListener('click', async () => {
          if (payButton.disabled) return;
          payButton.disabled = true;
          statusEl.textContent = 'Connecting to secure payment...';
          try {
            const reference = payButton.dataset.reference;
            const response = await fetch('/api/booking/checkout.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
              body: JSON.stringify({ reference })
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
              throw new Error(data.error || 'Unable to begin checkout.');
            }
            const sessionId = data.session.session_id;
            const { error } = await stripe.redirectToCheckout({ sessionId });
            if (error) throw error;
          } catch (error) {
            statusEl.textContent = error.message || 'Unable to redirect to payment.';
            payButton.disabled = false;
          }
        });
      })();
    </script>
</body>
</html>
