<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$pageTitle = 'Properties';
$activeNav = 'properties';
$properties = fetch_properties_grouped();

require_once __DIR__ . '/includes/header.php';
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-2xl font-semibold text-slate-800">Properties</h1>
    <div class="flex items-center gap-3">
        <a href="/admin/property-regenerate-all.php" class="text-sm text-slate-500 hover:text-slate-700 underline">Regenerate all</a>
        <a href="/admin/property-create.php" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
            <span class="text-lg">+</span>
            <span class="text-sm font-medium">Add Property</span>
        </a>
    </div>
</div>

<?php
$categoryLabels = [
    'villa'     => 'Villas',
    'apartment' => 'Apartments',
    'other'     => 'Other',
];
?>

<?php foreach ($categoryLabels as $key => $label): ?>
    <section class="mb-6">
        <header class="flex items-center justify-between mb-2">
            <h2 class="text-lg font-semibold text-slate-700"><?= htmlspecialchars($label) ?></h2>
            <span class="text-xs text-slate-500 uppercase tracking-wide"><?= count($properties[$key] ?? []) ?> listed</span>
        </header>

        <?php if (empty($properties[$key])): ?>
            <div class="bg-white border border-dashed border-slate-200 rounded p-6 text-sm text-slate-500">
                No <?= strtolower($label) ?> yet. <a class="text-indigo-600 hover:underline" href="/admin/property-create.php">Create the first one.</a>
            </div>
        <?php else: ?>
            <div class="grid gap-4 md:grid-cols-2">
                <?php foreach ($properties[$key] as $property): ?>
                    <article class="bg-white border border-slate-200 rounded-lg shadow-sm p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-800">
                                <?= htmlspecialchars($property['name']) ?>
                            </h3>
                            <span class="text-xs uppercase tracking-wide text-slate-500">#<?= (int) $property['id'] ?></span>
                        </div>
                        <p class="text-sm text-slate-600"><?= htmlspecialchars($property['headline'] ?? 'Untitled headline') ?></p>
                        <dl class="grid grid-cols-2 gap-2 text-xs text-slate-500">
                            <div>
                                <dt class="font-medium text-slate-600">Base rate</dt>
                                <dd>&euro;<?= number_format((float) ($property['base_rate'] ?? 0), 2) ?>/night</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-600">Gallery images</dt>
                                <dd><?= (int) ($property['gallery_count'] ?? 0) ?></dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-600">Amenities</dt>
                                <dd><?= (int) ($property['amenity_count'] ?? 0) ?></dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-600">Slug</dt>
                                <dd class="font-mono text-xs"><?= htmlspecialchars($property['slug'] ?? '') ?></dd>
                            </div>
                        </dl>
                        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-sm">
                            <div class="space-x-3">
                                <a href="/admin/property-edit.php?id=<?= (int) $property['id'] ?>" class="text-indigo-600 hover:underline">Edit</a>
                                <a href="/admin/property-regenerate.php?id=<?= (int) $property['id'] ?>" class="text-slate-500 hover:underline">Regenerate</a>
                            </div>
                            <a href="/properties/<?= htmlspecialchars($property['slug']) ?>/" target="_blank" class="text-indigo-600 hover:underline">View public page</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
