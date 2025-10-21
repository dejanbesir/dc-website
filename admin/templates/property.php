<?php
/**
 * @var array<string, mixed> $property
 * @var string $metaTitle
 * @var string $metaDescription
 * @var string $canonical
 * @var string $robots
 * @var string $ogTitle
 * @var string $ogDescription
 * @var string $ogImageAbsolute
 * @var string $twitterCard
 * @var array<int, array{path:string, alt:string, caption:string}> $galleryImages
 * @var string $schemaJson
 * @var array<int, array<string, mixed>> $quickFacts
 * @var array<int, array<string, mixed>> $amenities
 * @var array<int, array<string, mixed>> $seasons
 * @var array<int, string> $floorplanNotes
 * @var array<int, string> $descriptionParagraphs
 */

$heroImage = $property['hero_image'] ?? '';
$heroAlt = $property['hero_alt'] ?? $property['name'];
$heroCaption = $property['hero_caption'] ?? '';
$summary = trim((string) ($property['summary'] ?? ''));
$hasHeroGallery = count($galleryImages) > 0;
$publicSlug = $property['slug'];
$heroThumbs = $galleryImages;

$seasonHasRates = array_filter($seasons, static fn ($season) => !empty($season['nightly_rate']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($metaTitle) ?></title>
    <?php if ($metaDescription !== ''): ?>
        <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <meta name="robots" content="<?= htmlspecialchars($robots) ?>">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <?php if ($ogDescription !== ''): ?>
        <meta property="og:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <?php if ($ogImageAbsolute !== ''): ?>
        <meta property="og:image" content="<?= htmlspecialchars($ogImageAbsolute) ?>">
    <?php endif; ?>

    <meta name="twitter:card" content="<?= htmlspecialchars($twitterCard) ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <?php if ($ogDescription !== ''): ?>
        <meta name="twitter:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <?php endif; ?>
    <?php if ($ogImageAbsolute !== ''): ?>
        <meta name="twitter:image" content="<?= htmlspecialchars($ogImageAbsolute) ?>">
    <?php endif; ?>

    <script type="application/ld+json">
<?= $schemaJson . PHP_EOL ?>
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="flex flex-col min-h-screen text-gray-800">
<header class="bg-white shadow-md fixed w-full z-20">
    <div class="container mx-auto grid grid-cols-3 items-center py-4 px-6">
        <div><a href="/" class="text-2xl font-serif text-indigo-800">Dubrovnik Coast</a></div>
        <nav class="hidden md:flex justify-center space-x-6 text-sm">
            <a href="/" class="hover:text-indigo-600">Home</a>
            <a href="/properties/" class="hover:text-indigo-600">Properties</a>
            <a href="/our-story/" class="hover:text-indigo-600">Our Story</a>
            <a href="/blog/" class="hover:text-indigo-600">Blog</a>
            <a href="/contact/" class="hover:text-indigo-600">Contact</a>
        </nav>
        <div class="flex justify-end items-center space-x-4">
            <a href="/contact/" class="hidden md:inline-block bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Plan Your Stay</a>
            <button id="mobile-menu-button" class="md:hidden" aria-label="Open menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>
    <div id="mobile-menu" class="md:hidden hidden border-t border-gray-200 bg-white">
        <nav class="flex flex-col px-6 py-4 space-y-2 text-sm">
            <a href="/" class="py-1">Home</a>
            <a href="/properties/" class="py-1">Properties</a>
            <a href="/our-story/" class="py-1">Our Story</a>
            <a href="/blog/" class="py-1">Blog</a>
            <a href="/contact/" class="py-1">Contact</a>
        </nav>
    </div>
</header>
<div class="h-20"></div>

<main class="flex-grow">
    <?php if ($hasHeroGallery): ?>
        <section class="container mx-auto px-4 md:px-6 lg:px-8 mt-6" data-gallery-inline aria-label="Property gallery">
            <figure class="relative w-full h-[60vh] overflow-hidden rounded-lg bg-gray-100">
                <img
                    id="hero-main-image"
                    data-gallery-main
                    src="<?= htmlspecialchars($heroThumbs[0]['path']) ?>"
                    alt="<?= htmlspecialchars($heroThumbs[0]['alt'] ?: $property['name']) ?>"
                    class="h-full w-full object-cover"
                    loading="eager"
                    decoding="async"
                >
                <?php if (!empty($heroThumbs[0]['caption'])): ?>
                    <figcaption id="hero-caption" data-gallery-caption class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-sm px-4 py-2">
                        <?= htmlspecialchars($heroThumbs[0]['caption']) ?>
                    </figcaption>
                <?php endif; ?>
                <button type="button" class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full p-2 bg-black/30 text-white hover:bg-black/50" data-inline-prev aria-label="Previous image">&lsaquo;</button>
                <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full p-2 bg-black/30 text-white hover:bg-black/50" data-inline-next aria-label="Next image">&rsaquo;</button>
            </figure>

            <?php if (count($heroThumbs) > 1): ?>
                <div class="mt-3 flex gap-2 overflow-x-auto pb-1" role="listbox" aria-label="Gallery thumbnails">
                    <?php foreach ($heroThumbs as $index => $image): ?>
                        <button type="button"
                                role="option"
                                class="relative flex-none w-24 md:w-28 aspect-[4/3] overflow-hidden rounded-lg <?= $index === 0 ? 'ring-2 ring-indigo-500' : '' ?>"
                                data-gallery-thumb
                                data-full="<?= htmlspecialchars($image['path']) ?>"
                                data-alt="<?= htmlspecialchars($image['alt'] ?: $property['name']) ?>"
                                data-caption="<?= htmlspecialchars($image['caption'] ?? '') ?>"
                                aria-label="Show image <?= $index + 1 ?>"
                                aria-selected="<?= $index === 0 ? 'true' : 'false' ?>">
                            <img src="<?= htmlspecialchars($image['path']) ?>" alt="<?= htmlspecialchars($image['alt'] ?: $property['name']) ?>" class="h-full w-full object-cover" loading="lazy" decoding="async">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="container mx-auto px-6 py-12 space-y-12">
        <div class="space-y-2">
            <h1 class="text-4xl font-serif text-indigo-800"><?= htmlspecialchars($property['name']) ?></h1>
            <p class="text-lg text-gray-600"><?= htmlspecialchars($property['headline'] ?? '') ?></p>
            <?php if ($summary !== ''): ?>
                <p class="text-base text-gray-600"><?= htmlspecialchars($summary) ?></p>
            <?php endif; ?>
            <?php if (!empty($property['base_rate'])): ?>
                <p class="font-semibold text-xl text-gray-800">From &euro;<?= number_format((float) $property['base_rate'], 0) ?> per night</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($descriptionParagraphs)): ?>
            <section id="overview" class="space-y-4">
                <h2 class="text-2xl font-semibold text-gray-800">Overview</h2>
                <?php foreach ($descriptionParagraphs as $paragraph): ?>
                    <p class="text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($paragraph)) ?></p>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if (!empty($seasons)): ?>
            <section id="pricing" class="space-y-4">
                <h2 class="text-2xl font-semibold text-gray-800">Pricing by Season</h2>
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-gray-100 uppercase tracking-wide text-xs text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Season</th>
                            <th class="px-4 py-3">Dates</th>
                            <th class="px-4 py-3">Nightly rate</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <?php foreach ($seasons as $season): ?>
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-700"><?= htmlspecialchars($season['label'] ?? '') ?></td>
                                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($season['date_range'] ?? '') ?></td>
                                <td class="px-4 py-3 text-gray-800">
                                    <?php if (!empty($season['nightly_rate'])): ?>
                                        &euro;<?= number_format((float) $season['nightly_rate'], 0) ?>
                                    <?php else: ?>
                                        On request
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($seasonHasRates)): ?>
                    <p class="text-sm text-gray-500">Contact our concierge team for bespoke pricing.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if (!empty($quickFacts)): ?>
            <section id="facts">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Quick Facts</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center text-sm text-gray-600">
                    <?php foreach ($quickFacts as $fact): ?>
                        <div class="bg-white border border-gray-200 rounded-lg px-4 py-5 shadow-sm">
                            <div class="text-xl font-semibold text-indigo-700"><?= htmlspecialchars($fact['value'] ?? '') ?></div>
                            <div class="uppercase tracking-wide text-xs text-gray-500 mt-1"><?= htmlspecialchars($fact['label'] ?? '') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($amenities)): ?>
            <section id="amenities">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Amenities &amp; Services</h2>
                <ul class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm text-gray-600">
                    <?php foreach ($amenities as $amenity): ?>
                        <li class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                            <span><?= htmlspecialchars($amenity['label'] ?? '') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if (!empty($property['floorplan_image']) || !empty($floorplanNotes)): ?>
            <section id="floorplan" class="space-y-4">
                <h2 class="text-2xl font-semibold text-gray-800">Floor Plan</h2>
                <?php if (!empty($property['floorplan_image'])): ?>
                    <figure class="rounded-lg border border-gray-200 overflow-hidden shadow">
                        <img src="<?= htmlspecialchars($property['floorplan_image']) ?>" alt="<?= htmlspecialchars($property['floorplan_alt'] ?? $property['name']) ?>" class="w-full">
                        <?php if (!empty($property['floorplan_caption'])): ?>
                            <figcaption class="px-4 py-2 text-xs text-gray-500 bg-gray-50 border-t border-gray-200">
                                <?= htmlspecialchars($property['floorplan_caption']) ?>
                            </figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endif; ?>
                <?php if (!empty($floorplanNotes)): ?>
                    <ul class="list-disc pl-5 space-y-1 text-sm text-gray-600">
                        <?php foreach ($floorplanNotes as $note): ?>
                            <li><?= htmlspecialchars($note) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if (count($galleryImages) > 1): ?>
            <section id="gallery">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Gallery</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach (array_slice($galleryImages, 1) as $image): ?>
                        <figure class="relative">
                            <img src="<?= htmlspecialchars($image['path']) ?>" alt="<?= htmlspecialchars($image['alt'] ?: $property['name']) ?>" class="w-full h-40 object-cover rounded-lg shadow">
                            <?php if (!empty($image['caption'])): ?>
                                <figcaption class="absolute inset-x-0 bottom-0 bg-black/50 text-white text-xs px-2 py-1 rounded-b-lg">
                                    <?= htmlspecialchars($image['caption']) ?>
                                </figcaption>
                            <?php endif; ?>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section id="availability" class="space-y-4">
            <h2 class="text-2xl font-semibold text-gray-800">Availability</h2>
            <div class="bg-gray-100 h-64 flex items-center justify-center text-gray-500 rounded-lg border border-gray-200">
                Availability calendar integration coming soon. Contact our concierge for exact travel dates.
            </div>
        </section>

        <section id="location" class="space-y-4">
            <h2 class="text-2xl font-semibold text-gray-800">Location</h2>
            <?php if (!empty($property['map_embed'])): ?>
                <div class="rounded-lg overflow-hidden border border-gray-200 shadow">
                    <?= $property['map_embed'] ?>
                </div>
            <?php endif; ?>
            <div class="text-sm text-gray-600 bg-white border border-gray-200 rounded-lg px-4 py-3 shadow-sm">
                <div><?= htmlspecialchars($property['address_line'] ?? '') ?></div>
                <div><?= htmlspecialchars(trim(($property['postal_code'] ?? '') . ' ' . ($property['city'] ?? ''))) ?></div>
                <div><?= htmlspecialchars($property['country'] ?? '') ?></div>
            </div>
        </section>
    </section>
</main>

<footer class="bg-gray-900 text-gray-200 mt-auto">
    <div class="container mx-auto px-6 py-8 grid md:grid-cols-4 gap-6 text-sm">
        <div>
            <h3 class="font-serif text-lg text-white mb-3">Dubrovnik Coast</h3>
            <p class="text-gray-400">Tailor-made villa stays and bespoke concierge service across the Dalmatian coast.</p>
        </div>
        <div>
            <h3 class="font-semibold text-white mb-3">Discover</h3>
            <ul class="space-y-2">
                <li><a href="/properties/" class="hover:underline">All Properties</a></li>
                <li><a href="/blog/" class="hover:underline">Travel Stories</a></li>
                <li><a href="/contact/" class="hover:underline">Concierge Team</a></li>
            </ul>
        </div>
        <div>
            <h3 class="font-semibold text-white mb-3">Legal</h3>
            <ul class="space-y-2">
                <li><a href="/terms/" class="hover:underline">Terms &amp; Conditions</a></li>
                <li><a href="/privacy/" class="hover:underline">Privacy Policy</a></li>
                <li><a href="/cookies/" class="hover:underline">Cookie Policy</a></li>
            </ul>
        </div>
        <div>
            <h3 class="font-semibold text-white mb-3">Connect</h3>
            <p class="text-gray-400 mb-2">Follow us for inspiration and curated itineraries.</p>
            <div class="flex space-x-3 text-lg">
                <a href="https://www.facebook.com/dubrovnikcoast" class="hover:text-white">Fb</a>
                <a href="https://www.instagram.com/dubrovnikcoast" class="hover:text-white">Ig</a>
            </div>
        </div>
    </div>
    <div class="bg-gray-950 text-gray-500 text-xs text-center py-3">
        &copy; <?= date('Y') ?> Dubrovnik Coast. All rights reserved.
    </div>
</footer>

<script src="/main.js" defer></script>
</body>
</html>
