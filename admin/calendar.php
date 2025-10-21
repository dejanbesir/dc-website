<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$pageTitle = 'Calendar';
$activeNav = 'calendar';

require_once __DIR__ . '/includes/header.php';
?>

<section class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-4">
    <h1 class="text-2xl font-semibold text-slate-800">Availability Calendar</h1>
    <p class="text-slate-600 text-sm">
        Calendar integration will be added here. For now, you can manage property availability via your booking system,
        then we will sync it once the booking data model is ready.
    </p>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
