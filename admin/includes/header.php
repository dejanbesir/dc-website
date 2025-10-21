<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Admin Dashboard';
$activeNav = $activeNav ?? '';
$flash = get_flash_messages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> · Dubrovnik Coast Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
<header class="bg-indigo-900 text-white shadow">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="text-xl font-semibold">Dubrovnik Coast · Admin</div>
        <nav class="space-x-4 text-sm font-medium">
            <a href="/admin/dashboard.php" class="<?= $activeNav === 'overview' ? 'text-amber-300 underline' : 'hover:text-amber-200' ?>">Overview</a>
            <a href="/admin/properties.php" class="<?= $activeNav === 'properties' ? 'text-amber-300 underline' : 'hover:text-amber-200' ?>">Properties</a>
            <a href="/admin/calendar.php" class="<?= $activeNav === 'calendar' ? 'text-amber-300 underline' : 'hover:text-amber-200' ?>">Calendar</a>
            <a href="/admin/logout.php" class="hover:text-amber-200">Logout</a>
        </nav>
    </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <?php
                $bg = [
                    'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                    'error'   => 'bg-rose-50 text-rose-800 border-rose-200',
                    'info'    => 'bg-sky-50 text-sky-800 border-sky-200',
                ][$type] ?? 'bg-slate-50 text-slate-800 border-slate-200';
                ?>
                <div class="border <?= $bg ?> px-4 py-3 rounded"><?= htmlspecialchars($message) ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>
