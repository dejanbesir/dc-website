<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bookings.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

function get_current_admin_user(): ?string
{
    return $_SESSION['admin_email'] ?? 'admin';
}

$pdo = get_pdo();
$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($bookingId <= 0) {
    set_flash('error', 'Booking ID missing.');
    header('Location: /admin/calendar.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    update_booking_status($pdo, $bookingId, 'cancelled', 'admin', get_current_admin_user() ?? null, [
        'reason' => trim((string) ($_POST['reason'] ?? 'Cancelled by admin')),
    ]);
    send_booking_email($pdo, $bookingId, 'cancelled');
    set_flash('success', 'Booking cancelled.');
    header('Location: /admin/booking.php?id=' . $bookingId);
    exit;
}

$booking = fetch_booking($pdo, $bookingId);
if (!$booking) {
    set_flash('error', 'Booking not found.');
    header('Location: /admin/calendar.php');
    exit;
}

$pageTitle = 'Booking ' . $booking['reference'];
$activeNav = 'calendar';

require_once __DIR__ . '/includes/header.php';
?>

<section class="space-y-6">
    <header class="bg-white border border-slate-200 rounded-lg shadow-sm px-6 py-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-indigo-600">Booking</p>
            <h1 class="text-2xl font-semibold text-slate-800">Reference <?= htmlspecialchars($booking['reference']) ?></h1>
            <p class="text-sm text-slate-500">Property: <?= htmlspecialchars($booking['property_name']) ?></p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <span class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-wide">
                <?= htmlspecialchars($booking['status']) ?>
            </span>
            <a href="/admin/calendar.php?property=<?= (int) $booking['property_id'] ?>" class="px-3 py-2 border border-slate-300 rounded-lg hover:bg-slate-50">Property calendar</a>
            <a href="/properties/<?= htmlspecialchars($booking['property_slug']) ?>/" class="px-3 py-2 border border-slate-300 rounded-lg hover:bg-slate-50" target="_blank">View public page</a>
        </div>
    </header>

    <div class="grid lg:grid-cols-[2fr_1fr] gap-6">
        <section class="bg-white border border-slate-200 rounded-lg shadow-sm px-6 py-5 space-y-5">
            <h2 class="text-lg font-semibold text-slate-800">Stay details</h2>
            <dl class="grid md:grid-cols-2 gap-4 text-sm text-slate-600">
                <div>
                    <dt class="font-semibold text-slate-700">Arrival</dt>
                    <dd><?= htmlspecialchars($booking['arrival_date']) ?></dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-700">Departure</dt>
                    <dd><?= htmlspecialchars($booking['departure_date']) ?></dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-700">Nights</dt>
                    <dd><?= (int) $booking['nights'] ?></dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-700">Guests</dt>
                    <dd><?= (int) $booking['adults'] ?> adults<?php if ($booking['children']): ?>, <?= (int) $booking['children'] ?> children<?php endif; ?><?php if ($booking['infants']): ?>, <?= (int) $booking['infants'] ?> infants<?php endif; ?></dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-700">Total amount</dt>
                    <dd><?= number_format((float) $booking['total_amount'], 2) ?> <?= htmlspecialchars($booking['currency']) ?></dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-700">Stripe session</dt>
                    <dd><?= htmlspecialchars($booking['stripe_session_id'] ?? '—') ?></dd>
                </div>
            </dl>

            <h2 class="text-lg font-semibold text-slate-800 pt-4 border-t border-slate-100">Guest contact</h2>
            <?php if (!empty($booking['contact'])): ?>
                <dl class="grid md:grid-cols-2 gap-4 text-sm text-slate-600">
                    <div>
                        <dt class="font-semibold text-slate-700">Name</dt>
                        <dd><?= htmlspecialchars($booking['contact']['full_name']) ?></dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-700">Email</dt>
                        <dd><a href="mailto:<?= htmlspecialchars($booking['email']) ?>" class="text-indigo-600 hover:underline"><?= htmlspecialchars($booking['email']) ?></a></dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-700">Phone</dt>
                        <dd><a href="tel:<?= htmlspecialchars($booking['contact']['phone']) ?>" class="text-indigo-600 hover:underline"><?= htmlspecialchars($booking['contact']['phone']) ?></a></dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-700">Address</dt>
                        <dd>
                            <?= htmlspecialchars($booking['contact']['address_line']) ?><br>
                            <?= htmlspecialchars($booking['contact']['postal_code']) ?> <?= htmlspecialchars($booking['contact']['city']) ?><br>
                            <?= htmlspecialchars($booking['contact']['country']) ?>
                        </dd>
                    </div>
                </dl>
            <?php endif; ?>

            <?php if (!empty($booking['travellers'])): ?>
                <h2 class="text-lg font-semibold text-slate-800 pt-4 border-t border-slate-100">Travellers</h2>
                <ul class="space-y-2 text-sm text-slate-600">
                    <?php foreach ($booking['travellers'] as $traveller): ?>
                        <li>
                            <?= htmlspecialchars(ucfirst($traveller['traveller_type'])) ?>
                            <?php if ($traveller['age'] !== null): ?>
                                &middot; <?= (int) $traveller['age'] ?> years
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <aside class="space-y-6">
            <section class="bg-white border border-slate-200 rounded-lg shadow-sm px-6 py-5 space-y-4">
                <h2 class="text-lg font-semibold text-slate-800">Actions</h2>
                <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'expired'): ?>
                    <form method="post" class="space-y-3" onsubmit="return confirm('Cancel this booking?');">
                        <input type="hidden" name="action" value="cancel">
                        <label class="text-xs uppercase tracking-wide text-slate-500">Cancellation note</label>
                        <textarea name="reason" rows="3" class="w-full border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Optional note for the guest"></textarea>
                        <button type="submit" class="w-full px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition">
                            Cancel booking
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-sm text-slate-500">This booking is no longer active.</p>
                <?php endif; ?>
                <a href="/booking/export.php?property=<?= (int) $booking['property_id'] ?>&token=<?= urlencode(ensure_property_calendar($pdo, (int) $booking['property_id'])['export_token']) ?>" class="block text-sm text-indigo-600 hover:underline" target="_blank">Download ICS</a>
            </section>

            <section class="bg-white border border-slate-200 rounded-lg shadow-sm px-6 py-5 space-y-4">
                <h2 class="text-lg font-semibold text-slate-800">Event log</h2>
                <?php if (empty($booking['events'])): ?>
                    <p class="text-sm text-slate-500">No events recorded yet.</p>
                <?php else: ?>
                    <ul class="space-y-3 text-xs text-slate-600">
                        <?php foreach ($booking['events'] as $event): ?>
                            <li>
                                <div class="font-semibold text-slate-700"><?= htmlspecialchars($event['event_type']) ?></div>
                                <div><?= htmlspecialchars($event['created_at']) ?> &middot; <?= htmlspecialchars($event['actor_type']) ?></div>
                                <?php if (!empty($event['details'])): ?>
                                    <pre class="bg-slate-50 border border-slate-100 rounded px-2 py-1 mt-1 text-[11px] whitespace-pre-wrap"><?= htmlspecialchars($event['details']) ?></pre>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
