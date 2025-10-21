<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$pageTitle = 'Overview';
$activeNav = 'overview';

require_once __DIR__ . '/includes/header.php';
?>

<section class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm">
    <h2 class="text-xl font-semibold text-slate-800 mb-4">Recent Bookings</h2>
    <p class="text-slate-600 text-sm">
        Booking tracking will appear here once the reservation workflow is connected.
    </p>
</section>

<section class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm">
    <h2 class="text-xl font-semibold text-slate-800 mb-4">Quick Links</h2>
    <ul class="grid gap-3 md:grid-cols-2 text-sm text-indigo-700">
        <li><a href="/admin/property-create.php" class="hover:underline">Add a new property</a></li>
        <li><a href="/admin/properties.php" class="hover:underline">Manage existing properties</a></li>
        <li><a href="/admin/calendar.php" class="hover:underline">Set up availability calendar</a></li>
    </ul>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
