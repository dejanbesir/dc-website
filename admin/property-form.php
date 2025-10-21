<?php
declare(strict_types=1);

/** @var array<string, mixed> $property */
/** @var bool $isEdit */

$property = $property ?? [];
$isEdit = $isEdit ?? false;

$pageTitle = $isEdit ? 'Edit Property' : 'Add Property';
$activeNav = 'properties';

require_once __DIR__ . '/includes/header.php';

$facts = $property['quick_facts'] ?? [];
if (empty($facts)) {
    $facts = [['id' => null, 'label' => '', 'value' => '']];
}

$amenities = $property['amenities'] ?? [];
if (empty($amenities)) {
    $amenities = [['id' => null, 'label' => '']];
}

$seasons = $property['seasons'] ?? [];
if (empty($seasons)) {
    $seasons = [['id' => null, 'label' => '', 'date_range' => '', 'nightly_rate' => '']];
}

$gallery = $property['gallery'] ?? [];

$defaultRobots = $property['robots_directives'] ?? 'index,follow';
$defaultTwitterCard = $property['twitter_card'] ?? 'summary_large_image';

$seoSections = [
    'index,follow' => 'Index & Follow',
    'noindex,follow' => 'Noindex, Follow',
    'index,nofollow' => 'Index, Nofollow',
    'noindex,nofollow' => 'Noindex & Nofollow',
];

$twitterCardOptions = [
    'summary' => 'Summary',
    'summary_large_image' => 'Summary Large Image',
    'app' => 'App',
    'player' => 'Player',
];
?>

<form action="/admin/property-save.php" method="post" enctype="multipart/form-data" class="space-y-6">
    <?php if ($isEdit): ?>
        <input type="hidden" name="property_id" value="<?= (int) ($property['id'] ?? 0) ?>">
        <input type="hidden" name="original_slug" value="<?= htmlspecialchars($property['slug'] ?? '', ENT_QUOTES) ?>">
    <?php endif; ?>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-slate-800">
                <?= $isEdit ? 'Edit Property' : 'Create New Property' ?>
            </h1>
            <a href="/admin/properties.php" class="text-sm text-indigo-600 hover:underline">Back to properties</a>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Property Name</span>
                <input type="text" name="name" value="<?= htmlspecialchars($property['name'] ?? '', ENT_QUOTES) ?>" required class="border border-slate-300 rounded px-3 py-2" placeholder="Villa Anja">
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Slug / URL handle</span>
                <input type="text" name="slug" value="<?= htmlspecialchars($property['slug'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="villa-anja">
                <span class="text-xs text-slate-500">Leave blank to auto-generate from the name.</span>
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Category</span>
                <select name="category" required class="border border-slate-300 rounded px-3 py-2">
                    <?php
                    $categories = ['villa' => 'Villa', 'apartment' => 'Apartment', 'other' => 'Other'];
                    $selectedCategory = $property['category'] ?? 'villa';
                    foreach ($categories as $value => $label) :
                    ?>
                        <option value="<?= $value ?>" <?= $selectedCategory === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Base Rate (EUR)</span>
                <input type="text" name="base_rate" value="<?= htmlspecialchars((string) ($property['base_rate'] ?? ''), ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="450">
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Contact Phone</span>
                <input type="text" name="contact_phone" value="<?= htmlspecialchars($property['contact_phone'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="+38520123456">
            </label>
        </div>

        <label class="flex flex-col gap-1 text-sm">
            <span class="font-medium text-slate-700">Headline / Tagline</span>
            <input type="text" name="headline" value="<?= htmlspecialchars($property['headline'] ?? '', ENT_QUOTES) ?>" required class="border border-slate-300 rounded px-3 py-2" placeholder="Sleeps 6 - 3 Bedrooms - Private Pool & Garden">
        </label>

        <label class="flex flex-col gap-1 text-sm">
            <span class="font-medium text-slate-700">Lead Summary</span>
            <textarea name="summary" rows="3" class="border border-slate-300 rounded px-3 py-2" placeholder="One or two sentences used near the top of the property page."><?= htmlspecialchars($property['summary'] ?? '', ENT_QUOTES) ?></textarea>
        </label>

        <label class="flex flex-col gap-1 text-sm">
            <span class="font-medium text-slate-700">Full Description</span>
            <textarea name="description" rows="8" class="border border-slate-300 rounded px-3 py-2" placeholder="Long-form description used under the overview section."><?= htmlspecialchars($property['description'] ?? '', ENT_QUOTES) ?></textarea>
            <span class="text-xs text-slate-500">Use paragraph breaks to separate sections; HTML is allowed if needed.</span>
        </label>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
        <h2 class="text-xl font-semibold text-slate-800">SEO & Sharing</h2>

        <div class="grid md:grid-cols-2 gap-4">
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">HTML Title</span>
                <input type="text" name="page_title" value="<?= htmlspecialchars($property['page_title'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="Villa Anja - Dubrovnik Coast">
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Canonical URL</span>
                <input type="url" name="canonical_url" value="<?= htmlspecialchars($property['canonical_url'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="https://dubrovnik-coast.com/properties/villa-anja/">
            </label>
        </div>

        <label class="flex flex-col gap-1 text-sm">
            <span class="font-medium text-slate-700">Meta Description</span>
        <label class="flex flex-col gap-1 text-sm">
            <span class="font-medium text-slate-700">Meta Description</span>
            <textarea name="meta_description" rows="3" class="border border-slate-300 rounded px-3 py-2" placeholder="150-160 character pitch for search snippets."><?= htmlspecialchars($property['meta_description'] ?? '', ENT_QUOTES) ?></textarea>
        </label>
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Robots Directives</span>
                <select name="robots_directives" class="border border-slate-300 rounded px-3 py-2">
                    <?php foreach ($seoSections as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $defaultRobots === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                    <option value="custom" <?= !array_key_exists($defaultRobots, $seoSections) ? 'selected' : '' ?>>Custom</option>
                </select>
                <input
                    type="text"
                    name="robots_custom"
                    value="<?= !array_key_exists($defaultRobots, $seoSections) ? htmlspecialchars($defaultRobots, ENT_QUOTES) : '' ?>"
                    class="mt-2 border border-slate-300 rounded px-3 py-2 <?= array_key_exists($defaultRobots, $seoSections) ? 'hidden' : '' ?>"
                    data-robots-custom
                    placeholder="index,follow"
                >
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Twitter Card Type</span>
                <select name="twitter_card" class="border border-slate-300 rounded px-3 py-2">
                    <?php foreach ($twitterCardOptions as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $defaultTwitterCard === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">OpenGraph Title</span>
                <input type="text" name="og_title" value="<?= htmlspecialchars($property['og_title'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="Luxury Villa Anja">
            </label>
        </div>

        <label class="flex flex-col gap-1 text-sm">
            <span class="font-medium text-slate-700">OpenGraph Description</span>
            <textarea name="og_description" rows="3" class="border border-slate-300 rounded px-3 py-2" placeholder="Used for Facebook & LinkedIn shares."><?= htmlspecialchars($property['og_description'] ?? '', ENT_QUOTES) ?></textarea>
        </label>

        <div class="grid md:grid-cols-2 gap-4">
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">OpenGraph Image (optional)</span>
                <?php if (!empty($property['og_image'])): ?>
                    <div class="flex items-center justify-between rounded border border-slate-200 px-3 py-2 bg-slate-50 gap-3">
                        <span class="text-xs text-slate-600 truncate"><?= htmlspecialchars($property['og_image'], ENT_QUOTES) ?></span>
                        <label class="inline-flex items-center gap-2 text-rose-600 cursor-pointer text-xs">
                            <input type="checkbox" name="og_image_remove" value="1" class="h-4 w-4 border border-rose-300 rounded">
                            <span>Remove</span>
                        </label>
                    </div>
                    <input type="hidden" name="existing_og_image" value="<?= htmlspecialchars($property['og_image'], ENT_QUOTES) ?>">
                <?php endif; ?>
                <input type="file" name="og_image_upload" accept="image/*" class="border border-slate-300 rounded px-3 py-2">
                <span class="text-xs text-slate-500">Recommended 1200x630px.</span>
            </label>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
        <h2 class="text-xl font-semibold text-slate-800">Location</h2>

        <div class="grid md:grid-cols-2 gap-4">
            <label class="flex flex-col gap-1 text-sm md:col-span-2">
                <span class="font-medium text-slate-700">Street Address</span>
                <input type="text" name="address_line" value="<?= htmlspecialchars($property['address_line'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="Stara Mokosica bb">
            </label>
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">City</span>
                <input type="text" name="city" value="<?= htmlspecialchars($property['city'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="Dubrovnik">
            </label>
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Region</span>
                <input type="text" name="region" value="<?= htmlspecialchars($property['region'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="Dubrovnik-Neretva">
            </label>
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Postal Code</span>
                <input type="text" name="postal_code" value="<?= htmlspecialchars($property['postal_code'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="20218">
            </label>
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Country</span>
                <input type="text" name="country" value="<?= htmlspecialchars($property['country'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="Croatia">
            </label>
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Latitude</span>
                <input type="text" name="latitude" value="<?= htmlspecialchars((string) ($property['latitude'] ?? ''), ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="42.6507">
            </label>
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Longitude</span>
                <input type="text" name="longitude" value="<?= htmlspecialchars((string) ($property['longitude'] ?? ''), ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="18.0944">
            </label>
            <label class="flex flex-col gap-1 text-sm md:col-span-2">
                <span class="font-medium text-slate-700">Google Map Embed Code</span>
                <textarea name="map_embed" rows="3" class="border border-slate-300 rounded px-3 py-2" placeholder="Paste the iframe embed code here."><?= htmlspecialchars($property['map_embed'] ?? '', ENT_QUOTES) ?></textarea>
            </label>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
        <h2 class="text-xl font-semibold text-slate-800">Quick Facts</h2>
        <p class="text-sm text-slate-500">Add high-level facts such as guests, bedrooms, pool, Wi-Fi, parking, etc.</p>

        <div class="space-y-2" data-collection="facts">
            <?php foreach ($facts as $fact): ?>
                <div class="grid md:grid-cols-2 gap-3 items-end bg-slate-50 border border-slate-200 rounded px-3 py-3" data-collection-row>
                    <input type="hidden" name="fact_id[]" value="<?= (int) ($fact['id'] ?? 0) ?>">
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium text-slate-700">Label</span>
                        <input type="text" name="fact_label[]" value="<?= htmlspecialchars($fact['label'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="Guests">
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium text-slate-700">Value</span>
                        <input type="text" name="fact_value[]" value="<?= htmlspecialchars($fact['value'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2" placeholder="6">
                    </label>
                    <button type="button" class="text-xs text-rose-600 hover:underline md:justify-self-end" data-remove-row>Remove</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" data-add-row="facts" class="text-sm text-indigo-600 hover:underline">Add another fact</button>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
        <h2 class="text-xl font-semibold text-slate-800">Amenities</h2>
        <p class="text-sm text-slate-500">List stand-out services or features.</p>

        <div class="space-y-2" data-collection="amenities">
            <?php foreach ($amenities as $amenity): ?>
                <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded px-3 py-3" data-collection-row>
                    <input type="hidden" name="amenity_id[]" value="<?= (int) ($amenity['id'] ?? 0) ?>">
                    <input type="text" name="amenity[]" value="<?= htmlspecialchars($amenity['label'] ?? '', ENT_QUOTES) ?>" class="flex-1 border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Private Pool">
                    <button type="button" class="text-xs text-rose-600 hover:underline" data-remove-row>Remove</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" data-add-row="amenities" class="text-sm text-indigo-600 hover:underline">Add another amenity</button>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
        <h2 class="text-xl font-semibold text-slate-800">Seasonal Pricing</h2>
        <p class="text-sm text-slate-500">Add seasonal price bands (label, date range, nightly rate).</p>

        <div class="space-y-2" data-collection="seasons">
            <?php foreach ($seasons as $season): ?>
                <div class="grid md:grid-cols-4 gap-3 items-end bg-slate-50 border border-slate-200 rounded px-3 py-3" data-collection-row>
                    <input type="hidden" name="season_id[]" value="<?= (int) ($season['id'] ?? 0) ?>">
                    <input type="text" name="season_label[]" value="<?= htmlspecialchars($season['label'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2 text-sm" placeholder="High Season">
                    <input type="text" name="season_dates[]" value="<?= htmlspecialchars($season['date_range'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Jul 1 - Aug 31">
                    <input type="text" name="season_rate[]" value="<?= htmlspecialchars((string) ($season['nightly_rate'] ?? ''), ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2 text-sm" placeholder="600">
                    <button type="button" class="text-xs text-rose-600 hover:underline md:justify-self-end" data-remove-row>Remove</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" data-add-row="seasons" class="text-sm text-indigo-600 hover:underline">Add another season</button>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
        <h2 class="text-xl font-semibold text-slate-800">Floor Plan & Highlights</h2>

        <label class="flex flex-col gap-1 text-sm">
            <span class="font-medium text-slate-700">Floor Plan Notes</span>
            <textarea name="floorplan_notes" rows="3" class="border border-slate-300 rounded px-3 py-2" placeholder="Ground Floor: Living/dining/kitchen, guest WC"><?= htmlspecialchars($property['floorplan_notes'] ?? '', ENT_QUOTES) ?></textarea>
        </label>

        <div class="grid md:grid-cols-2 gap-4">
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Floor Plan Image</span>
                <?php if (!empty($property['floorplan_image'])): ?>
                    <div class="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 flex justify-between gap-3 items-center">
                        <span class="truncate"><?= htmlspecialchars($property['floorplan_image'], ENT_QUOTES) ?></span>
                        <label class="inline-flex items-center gap-2 text-rose-600 cursor-pointer">
                            <input type="checkbox" name="floorplan_remove" value="1" class="h-4 w-4 border border-rose-300 rounded">
                            <span>Remove</span>
                        </label>
                    </div>
                    <input type="hidden" name="existing_floorplan_image" value="<?= htmlspecialchars($property['floorplan_image'], ENT_QUOTES) ?>">
                <?php endif; ?>
                <input type="file" name="floorplan_image" accept="image/*" class="border border-slate-300 rounded px-3 py-2">
            </label>
            <div class="space-y-3">
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-slate-700">Floor Plan Alt Text</span>
                    <input type="text" name="floorplan_alt" value="<?= htmlspecialchars($property['floorplan_alt'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2">
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-slate-700">Floor Plan Caption</span>
                    <textarea name="floorplan_caption" rows="2" class="border border-slate-300 rounded px-3 py-2"><?= htmlspecialchars($property['floorplan_caption'] ?? '', ENT_QUOTES) ?></textarea>
                </label>
            </div>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
        <h2 class="text-xl font-semibold text-slate-800">Images</h2>

        <div class="grid md:grid-cols-2 gap-4">
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">Hero Image</span>
                <?php if (!empty($property['hero_image'])): ?>
                    <div class="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 flex justify-between gap-3 items-center">
                        <span class="truncate"><?= htmlspecialchars($property['hero_image'], ENT_QUOTES) ?></span>
                        <label class="inline-flex items-center gap-2 text-rose-600 cursor-pointer">
                            <input type="checkbox" name="hero_remove" value="1" class="h-4 w-4 border border-rose-300 rounded">
                            <span>Remove</span>
                        </label>
                    </div>
                    <input type="hidden" name="existing_hero_image" value="<?= htmlspecialchars($property['hero_image'], ENT_QUOTES) ?>">
                <?php endif; ?>
                <input type="file" name="hero_image" accept="image/*" class="border border-slate-300 rounded px-3 py-2">
            </label>
            <div class="space-y-3">
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-slate-700">Hero Image Alt Text</span>
                    <input type="text" name="hero_alt" value="<?= htmlspecialchars($property['hero_alt'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-3 py-2">
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-slate-700">Hero Image Caption</span>
                    <textarea name="hero_caption" rows="2" class="border border-slate-300 rounded px-3 py-2"><?= htmlspecialchars($property['hero_caption'] ?? '', ENT_QUOTES) ?></textarea>
                </label>
            </div>
        </div>

        <label class="flex flex-col gap-1 text-sm">
            <span class="font-medium text-slate-700">Gallery Images</span>
            <input type="file" name="gallery_images[]" accept="image/*" multiple data-gallery-input class="border border-slate-300 rounded px-3 py-2">
            <span class="text-xs text-slate-500">Upload new images to append to the gallery.</span>
        </label>

        <?php if (!empty($gallery)): ?>
            <div class="border border-slate-200 rounded-lg overflow-hidden">
                <div class="bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Existing Gallery</div>
                <div class="divide-y divide-slate-200">
                    <?php foreach ($gallery as $item): ?>
                        <div class="grid md:grid-cols-4 gap-3 px-4 py-3 text-sm items-start">
                            <div class="md:col-span-2 space-y-2">
                                <div class="flex items-center justify-between text-xs text-slate-500">
                                    <span class="font-mono"><?= htmlspecialchars($item['image_path'] ?? '', ENT_QUOTES) ?></span>
                                    <label class="inline-flex items-center gap-2 text-rose-600 cursor-pointer">
                                        <input type="checkbox" name="gallery_existing[<?= (int) $item['id'] ?>][remove]" value="1" class="h-4 w-4 border border-rose-300 rounded">
                                        <span>Remove</span>
                                    </label>
                                </div>
                                <input type="hidden" name="gallery_existing[<?= (int) $item['id'] ?>][path]" value="<?= htmlspecialchars($item['image_path'] ?? '', ENT_QUOTES) ?>">
                                <label class="flex flex-col gap-1">
                                    <span class="text-xs font-medium text-slate-600">Alt Text</span>
                                    <input type="text" name="gallery_existing[<?= (int) $item['id'] ?>][alt]" value="<?= htmlspecialchars($item['alt_text'] ?? '', ENT_QUOTES) ?>" class="border border-slate-300 rounded px-2 py-1">
                                </label>
                            </div>
                            <label class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-slate-600">Caption</span>
                                <textarea name="gallery_existing[<?= (int) $item['id'] ?>][caption]" rows="2" class="border border-slate-300 rounded px-2 py-1"><?= htmlspecialchars($item['caption'] ?? '', ENT_QUOTES) ?></textarea>
                            </label>
                            <label class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-slate-600">Sort Order</span>
                                <input type="number" name="gallery_existing[<?= (int) $item['id'] ?>][sort_order]" value="<?= (int) ($item['sort_order'] ?? 0) ?>" class="border border-slate-300 rounded px-2 py-1">
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex justify-end items-center bg-slate-50 px-4 py-2 border-t border-slate-200">
                    <label class="inline-flex items-center gap-2 text-rose-600 cursor-pointer text-xs">
                        <input type="checkbox" name="gallery_remove_all" value="1" class="h-4 w-4 border border-rose-300 rounded">
                        <span>Remove all existing images</span>
                    </label>
                </div>
            </div>
        <?php endif; ?>

        <div class="space-y-2" data-gallery-alt></div>
        <p class="text-xs text-slate-500">
            After selecting gallery files, provide alt text and optional caption for each image.
        </p>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
        <h2 class="text-xl font-semibold text-slate-800">Structured Data (JSON-LD)</h2>
        <p class="text-sm text-slate-500">
            Provide a JSON-LD payload for Schema.org markup. A default template is generated automatically; adjust as needed for rich results.
        </p>
        <textarea name="schema_json" rows="12" class="font-mono text-xs border border-slate-300 rounded px-3 py-2"><?= htmlspecialchars($property['schema_json'] ?? '', ENT_NOQUOTES) ?></textarea>
    </section>

    <div class="flex items-center justify-end gap-3 pb-6">
        <a href="/admin/properties.php" class="px-4 py-2 rounded border border-slate-300 text-sm text-slate-600 hover:bg-slate-50">Cancel</a>
        <button type="submit" class="px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Save Property</button>
    </div>
</form>

<template id="template-facts">
    <div class="grid md:grid-cols-2 gap-3 items-end bg-slate-50 border border-slate-200 rounded px-3 py-3" data-collection-row>
        <input type="hidden" name="fact_id[]" value="0">
        <label class="flex flex-col gap-1 text-sm">
            <span class="font-medium text-slate-700">Label</span>
            <input type="text" name="fact_label[]" class="border border-slate-300 rounded px-3 py-2" placeholder="Guests">
        </label>
        <label class="flex flex-col gap-1 text-sm">
            <span class="font-medium text-slate-700">Value</span>
            <input type="text" name="fact_value[]" class="border border-slate-300 rounded px-3 py-2" placeholder="6">
        </label>
        <button type="button" class="text-xs text-rose-600 hover:underline md:justify-self-end" data-remove-row>Remove</button>
    </div>
</template>

<template id="template-amenities">
    <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded px-3 py-3" data-collection-row>
        <input type="hidden" name="amenity_id[]" value="0">
        <input type="text" name="amenity[]" class="flex-1 border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Air-Con">
        <button type="button" class="text-xs text-rose-600 hover:underline" data-remove-row>Remove</button>
    </div>
</template>

<template id="template-seasons">
    <div class="grid md:grid-cols-4 gap-3 items-end bg-slate-50 border border-slate-200 rounded px-3 py-3" data-collection-row>
        <input type="hidden" name="season_id[]" value="0">
        <input type="text" name="season_label[]" class="border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Low Season">
        <input type="text" name="season_dates[]" class="border border-slate-300 rounded px-3 py-2 text-sm" placeholder="Apr 1 - May 31">
        <input type="text" name="season_rate[]" class="border border-slate-300 rounded px-3 py-2 text-sm" placeholder="350">
        <button type="button" class="text-xs text-rose-600 hover:underline md:justify-self-end" data-remove-row>Remove</button>
    </div>
</template>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
