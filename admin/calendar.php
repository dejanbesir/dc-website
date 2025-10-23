<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/bookings.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$pdo = get_pdo();
$pageTitle = 'Calendar';
$activeNav = 'calendar';

$propertyId = isset($_GET['property']) ? (int) $_GET['property'] : null;
$search = isset($_GET['q']) ? trim((string) $_GET['q']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $propertyId) {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_feeds') {
        update_property_calendar_feeds($pdo, $propertyId, [
            'airbnb' => trim((string) ($_POST['airbnb_feed_url'] ?? '')),
            'booking' => trim((string) ($_POST['booking_feed_url'] ?? '')),
            'custom' => trim((string) ($_POST['custom_feed_url'] ?? '')),
        ]);
        set_flash('success', 'Calendar feeds updated.');
        header('Location: /admin/calendar.php?property=' . $propertyId);
        exit;
    }

    if ($action === 'sync_feeds') {
        $result = sync_property_calendar_from_feeds($pdo, $propertyId);
        set_flash('success', sprintf('Imported %d events from %d feed(s).', $result['imported'], $result['feeds']));
        header('Location: /admin/calendar.php?property=' . $propertyId);
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';

if ($propertyId !== null && $propertyId > 0) {
    renderPropertyCalendar($pdo, $propertyId);
} else {
    renderCalendarOverview($pdo, $search);
}

require_once __DIR__ . '/includes/footer.php';

/**
 * Displays overall bookings list with filters.
 */
function renderCalendarOverview(PDO $pdo, ?string $search): void
{
    $properties = $pdo->query('SELECT id, name, slug FROM properties ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
    $bookings = fetch_bookings($pdo, null, $search ?: null);
    ?>
    <section class="space-y-6">
        <header class="bg-white border border-slate-200 rounded-lg shadow-sm px-6 py-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Booking Calendar</h1>
                <p class="text-sm text-slate-500">View upcoming stays and jump into property calendars for a detailed view.</p>
            </div>
            <a href="/admin/calendar.php" class="text-sm text-slate-500 hover:text-slate-700 underline">Reset filters</a>
        </header>

        <form method="get" class="bg-white border border-slate-200 rounded-lg shadow-sm px-6 py-4 grid md:grid-cols-[1fr_auto] gap-4 text-sm">
            <label class="block">
                <span class="text-slate-600 mb-1 inline-block" for="q">Search bookings</span>
                <input id="q" name="q" type="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Reference, property, or guest" class="w-full border border-slate-300 rounded px-3 py-2">
            </label>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Search</button>
            </div>
        </form>

        <section class="bg-white border border-slate-200 rounded-lg shadow-sm px-6 py-5 space-y-3 text-sm">
            <h2 class="text-lg font-semibold text-slate-800">Open property calendar</h2>
            <p class="text-slate-500">Select a property to view its availability grid, ICS feeds, and bookings.</p>
            <form method="get" action="/admin/calendar.php" class="flex flex-wrap gap-3">
                <label class="flex-1 min-w-[220px]">
                    <span class="sr-only">Property</span>
                    <select name="property" class="w-full border border-slate-300 rounded px-3 py-2" required>
                        <option value="" disabled selected>Select property</option>
                        <?php foreach ($properties as $property): ?>
                            <option value="<?= (int) $property['id'] ?>"><?= htmlspecialchars($property['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    View calendar
                </button>
            </form>
        </section>

        <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wide text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Reference</th>
                        <th class="px-4 py-3 text-left font-medium">Property</th>
                        <th class="px-4 py-3 text-left font-medium">Dates</th>
                        <th class="px-4 py-3 text-left font-medium">Guests</th>
                        <th class="px-4 py-3 text-left font-medium">Total</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$bookings): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">No bookings found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-xs text-slate-600"><?= htmlspecialchars($booking['reference']) ?></td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div class="font-medium text-slate-800"><?= htmlspecialchars($booking['property_name']) ?></div>
                                    <a href="/admin/calendar.php?property=<?= (int) $booking['property_id'] ?>" class="text-xs text-indigo-600 hover:underline">
                                        Show calendar
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <?= htmlspecialchars($booking['arrival_date']) ?> &rarr; <?= htmlspecialchars($booking['departure_date']) ?>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <?= (int) $booking['adults'] ?> adults<?php if ($booking['children'] > 0): ?>, <?= (int) $booking['children'] ?> children<?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <?= number_format((float) $booking['total_amount'], 2) ?> <?= htmlspecialchars($booking['currency']) ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs border border-slate-200 bg-slate-50 text-slate-600">
                                        <?= htmlspecialchars($booking['status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="/admin/booking.php?id=<?= (int) $booking['id'] ?>" class="text-indigo-600 hover:underline">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
}

/**
 * Renders a per-property calendar.
 */
function renderPropertyCalendar(PDO $pdo, int $propertyId): void
{
    $stmt = $pdo->prepare('SELECT * FROM properties WHERE id = :id');
    $stmt->execute([':id' => $propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$property) {
        echo '<section class="bg-white border border-slate-200 rounded-lg shadow-sm px-6 py-6"><p>Property not found.</p></section>';
        return;
    }

    $monthParam = isset($_GET['month']) ? $_GET['month'] : (new DateTimeImmutable('first day of this month'))->format('Y-m-01');
    $currentMonth = new DateTimeImmutable($monthParam);
    $start = $currentMonth->format('Y-m-01');
    $blocks = fetch_calendar_blocks($pdo, $propertyId, $start, $currentMonth->modify('+2 months')->format('Y-m-01'));
    $calendar = ensure_property_calendar($pdo, $propertyId);
    $exportUrl = SITE_BASE_URL . '/booking/export.php?property=' . $propertyId . '&token=' . urlencode($calendar['export_token']);
    $scope = $_GET['scope'] ?? 'all';
    ?>
    <section class="space-y-6">
        <header class="bg-white border border-slate-200 rounded-lg shadow-sm px-6 py-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800"><?= htmlspecialchars($property['name']) ?> calendar</h1>
                <p class="text-sm text-slate-500">Hover any booking to see the reference. Click through to view guest details.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="/admin/calendar.php" class="text-sm text-slate-500 hover:text-slate-700 underline">Back to all bookings</a>
                <a href="<?= htmlspecialchars($exportUrl) ?>" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm text-slate-700" target="_blank" rel="noopener">
                    Download ICS feed
                </a>
            </div>
        </header>

        <section class="bg-white border border-slate-200 rounded-lg shadow-sm px-6 py-5 space-y-4">
            <h2 class="text-lg font-semibold text-slate-800">Calendar feeds</h2>
            <form method="post" class="grid md:grid-cols-3 gap-4 text-sm">
                <label class="block">
                    <span class="text-slate-600 mb-1 inline-block">Airbnb feed URL</span>
                    <input type="url" name="airbnb_feed_url" value="<?= htmlspecialchars($calendar['airbnb_feed_url'] ?? '') ?>" class="w-full border border-slate-300 rounded px-3 py-2">
                </label>
                <label class="block">
                    <span class="text-slate-600 mb-1 inline-block">Booking.com feed URL</span>
                    <input type="url" name="booking_feed_url" value="<?= htmlspecialchars($calendar['booking_feed_url'] ?? '') ?>" class="w-full border border-slate-300 rounded px-3 py-2">
                </label>
                <label class="block">
                    <span class="text-slate-600 mb-1 inline-block">Additional ICS feed</span>
                    <input type="url" name="custom_feed_url" value="<?= htmlspecialchars($calendar['custom_feed_url'] ?? '') ?>" class="w-full border border-slate-300 rounded px-3 py-2">
                </label>
                <div class="md:col-span-3 flex items-center gap-3">
                    <button type="submit" name="action" value="update_feeds" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Save feeds</button>
                    <button type="submit" name="action" value="sync_feeds" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition">Sync now</button>
                    <span class="text-xs text-slate-500">Last sync: <?= $calendar['last_sync_at'] ? htmlspecialchars($calendar['last_sync_at']) : 'Never' ?></span>
                </div>
            </form>
            <p class="text-xs text-slate-500">
                Give these URLs to Airbnb or Booking.com to import Dubvrovnik Coast availability:
                <code class="bg-slate-100 border border-slate-200 px-2 py-1 rounded text-[11px]"><?= htmlspecialchars($exportUrl) ?></code>
            </p>
        </section>

        <section class="bg-white border border-slate-200 rounded-lg shadow-sm px-6 py-5 space-y-5">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800"><?= $currentMonth->format('F Y') ?> &amp; <?= $currentMonth->modify('+1 month')->format('F Y') ?></h2>
                    <p class="text-xs text-slate-500">Grey dates are unavailable (booked, blocked, or imported from external feeds).</p>
                </div>
                <div class="flex gap-2 text-sm">
                    <a href="/admin/calendar.php?property=<?= $propertyId ?>&month=<?= $currentMonth->modify('-1 month')->format('Y-m-01') ?>" class="px-3 py-1 border border-slate-300 rounded hover:bg-slate-50">&larr; Previous</a>
                    <a href="/admin/calendar.php?property=<?= $propertyId ?>&month=<?= $currentMonth->modify('+1 month')->format('Y-m-01') ?>" class="px-3 py-1 border border-slate-300 rounded hover:bg-slate-50">Next &rarr;</a>
                </div>
            </div>
            <div class="grid lg:grid-cols-2 gap-4">
                <?php
                renderMonthGrid($currentMonth, $blocks);
                renderMonthGrid($currentMonth->add(new DateInterval('P1M')), $blocks);
                ?>
            </div>
        </section>

        <?php renderPropertyBookingsTable($pdo, $propertyId, $property['name'] ?? '', $scope); ?>
    </section>
    <?php
}

/**
 * Renders a single month calendar grid with blocks.
 */
function renderMonthGrid(DateTimeImmutable $month, array $blocks): void
{
    $firstDay = $month->modify('first day of this month');
    $lastDay = $month->modify('last day of this month');
    $startWeekday = ((int) $firstDay->format('N')) - 1; // 0 = Monday
    $daysInMonth = (int) $lastDay->format('j');

    $blockMap = [];
    foreach ($blocks as $block) {
        $start = new DateTimeImmutable($block['start_date']);
        $end = new DateTimeImmutable($block['end_date']);

        $current = $start;
        while ($current < $end) {
            $key = $current->format('Y-m-d');
            $blockMap[$key] = $block;
            $current = $current->modify('+1 day');
        }
    }
    ?>
    <div class="grid grid-cols-7 gap-px bg-slate-200 rounded overflow-hidden text-sm">
        <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $weekday): ?>
            <div class="bg-slate-50 text-center py-2 text-xs font-medium text-slate-600"><?= $weekday ?></div>
        <?php endforeach; ?>
        <?php for ($i = 0; $i < $startWeekday; $i += 1): ?>
            <div class="bg-white py-6"></div>
        <?php endfor; ?>
        <?php for ($day = 1; $day <= $daysInMonth; $day += 1): ?>
            <?php
            $date = $month->setDate((int) $month->format('Y'), (int) $month->format('n'), $day);
            $key = $date->format('Y-m-d');
            $block = $blockMap[$key] ?? null;
            $isBlocked = $block !== null;
            ?>
            <div
                class="min-h-[78px] px-2 py-2 <?= $isBlocked ? 'bg-slate-200' : 'bg-white' ?> flex flex-col justify-between"
                title="<?= $isBlocked ? htmlspecialchars(($block['reference'] ?? $block['title'] ?? 'Booked') . ' • ' . $block['start_date'] . ' to ' . $block['end_date']) : '' ?>"
            >
                <div class="text-xs font-semibold text-slate-600"><?= $day ?></div>
                <?php if ($isBlocked): ?>
                    <div class="text-[11px] text-slate-700 leading-tight mt-1">
                        <?= htmlspecialchars($block['reference'] ?? $block['title'] ?? 'Booked') ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
    <?php
}

/**
 * Booking table with scope filters (past/current/future/all).
 */
function renderPropertyBookingsTable(PDO $pdo, int $propertyId, string $propertyName, string $scope): void
{
    $bookings = fetch_bookings($pdo, $propertyId, null);
    $scope = in_array($scope, ['all', 'past', 'current', 'future'], true) ? $scope : 'all';
    $today = new DateTimeImmutable('today');

    $filtered = array_filter($bookings, static function (array $booking) use ($scope, $today): bool {
        $arrival = new DateTimeImmutable($booking['arrival_date']);
        $departure = new DateTimeImmutable($booking['departure_date']);

        $category = 'future';
        if ($departure < $today) {
            $category = 'past';
        } elseif ($arrival <= $today && $departure >= $today) {
            $category = 'current';
        }

        return $scope === 'all' ? true : $scope === $category;
    });

    $scopes = [
        'all' => 'All',
        'current' => 'Current (in-house)',
        'future' => 'Upcoming',
        'past' => 'Past',
    ];

    ?>
    <section class="bg-white border border-slate-200 rounded-lg shadow-sm px-6 py-5 space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h2 class="text-lg font-semibold text-slate-800">Bookings for <?= htmlspecialchars($propertyName) ?></h2>
            <div class="flex flex-wrap gap-2 text-sm">
                <?php foreach ($scopes as $key => $label): ?>
                    <a
                        href="/admin/calendar.php?property=<?= $propertyId ?>&scope=<?= $key ?>"
                        class="px-3 py-1 rounded-full border <?= $scope === $key ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 text-slate-600 hover:bg-slate-50' ?>"
                    >
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!$filtered): ?>
            <p class="text-sm text-slate-500">No bookings match this filter.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-slate-600 uppercase tracking-wide text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Reference</th>
                            <th class="px-4 py-3 text-left font-medium">Dates</th>
                            <th class="px-4 py-3 text-left font-medium">Guests</th>
                            <th class="px-4 py-3 text-left font-medium">Amount</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($filtered as $booking): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-xs text-slate-600"><?= htmlspecialchars($booking['reference']) ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($booking['arrival_date']) ?> &rarr; <?= htmlspecialchars($booking['departure_date']) ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= (int) $booking['adults'] ?> adults<?php if ($booking['children']): ?>, <?= (int) $booking['children'] ?> children<?php endif; ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= number_format((float) $booking['total_amount'], 2) ?> <?= htmlspecialchars($booking['currency']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs border border-slate-200 bg-slate-50 text-slate-600">
                                        <?= htmlspecialchars($booking['status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="/admin/booking.php?id=<?= (int) $booking['id'] ?>" class="text-indigo-600 hover:underline">View booking</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <?php
}
