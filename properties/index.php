<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../includes/homepage.php';

/**
 * @return array<string, array<int, array<string, mixed>>>
 */
function fetch_property_catalog(): array
{
    $pdo = get_pdo();

    $sql = <<<SQL
        SELECT
            id,
            slug,
            category,
            name,
            headline,
            summary,
            base_rate,
            hero_image,
            hero_alt
        FROM properties
        WHERE hero_image IS NOT NULL
          AND hero_image <> ''
        ORDER BY
            CASE
                WHEN category = 'villa' THEN 0
                WHEN category = 'apartment' THEN 1
                ELSE 2
            END,
            name ASC
    SQL;

    $stmt = $pdo->query($sql);

    $properties = [];
    $ids = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $category = $row['category'] ?? 'other';
        if ($category !== 'villa' && $category !== 'apartment') {
            $category = 'other';
        }

        $property = [
            'id'         => (int) $row['id'],
            'slug'       => (string) $row['slug'],
            'category'   => $category,
            'name'       => (string) $row['name'],
            'headline'   => trim((string) ($row['headline'] ?? '')),
            'summary'    => trim((string) ($row['summary'] ?? '')),
            'base_rate'  => isset($row['base_rate']) ? (float) $row['base_rate'] : null,
            'hero_image' => trim((string) ($row['hero_image'] ?? '')),
            'hero_alt'   => trim((string) ($row['hero_alt'] ?? '')),
        ];

        if ($property['hero_image'] === '') {
            continue;
        }

        $properties[] = $property;
        $ids[] = $property['id'];
    }

    if (!$properties) {
        return [
            'villa' => [],
            'apartment' => [],
            'other' => [],
        ];
    }

    $quickFacts = $ids ? load_property_quick_facts($pdo, $ids) : [];

    $grouped = [
        'villa'     => [],
        'apartment' => [],
        'other'     => [],
    ];

    foreach ($properties as $property) {
        $enriched = enrich_property_card($property, $quickFacts[$property['id']] ?? []);
        $category = $property['category'];
        if (!array_key_exists($category, $grouped)) {
            $grouped[$category] = [];
        }
        $grouped[$category][] = $enriched;
    }

    return $grouped;
}

/**
 * Normalises the hero image path for use in the public site.
 */
function catalog_image_src(array $property): string
{
    $src = trim((string) ($property['hero_image'] ?? ''));
    if ($src === '') {
        return '';
    }

    return $src[0] === '/' ? $src : '/' . ltrim($src, '/');
}

/**
 * Builds the highlight string used inside a property card.
 */
function build_catalog_highlight(array $property): string
{
    $highlights = $property['highlights'] ?? [];

    if ($highlights) {
        $escaped = array_map(
            static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
            $highlights
        );

        return implode(' &bull; ', $escaped);
    }

    $summary = trim((string) ($property['summary'] ?? ''));
    if ($summary !== '') {
        return htmlspecialchars($summary, ENT_QUOTES, 'UTF-8');
    }

    $headline = trim((string) ($property['headline'] ?? ''));
    if ($headline !== '') {
        return htmlspecialchars($headline, ENT_QUOTES, 'UTF-8');
    }

    return '';
}

$catalogError = null;
$propertyGroups = [
    'villa' => [],
    'apartment' => [],
    'other' => [],
];

try {
    $propertyGroups = fetch_property_catalog();
} catch (Throwable $exception) {
    $catalogError = 'We\'re updating our property listings. Please check back soon.';
}

$allProperties = array_merge(
    $propertyGroups['villa'] ?? [],
    $propertyGroups['apartment'] ?? [],
    $propertyGroups['other'] ?? []
);

if (!$catalogError && !$allProperties) {
    $catalogError = 'We\'re updating our property listings. Please check back soon.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>All Properties &mdash; Dubrovnik Coast</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html { scroll-behavior: smooth; }
  </style>
</head>
<body class="flex flex-col min-h-screen font-sans text-gray-800">

<!-- ========== HEADER / NAVBAR ========== -->
<header class="bg-white shadow-md fixed w-full z-20">
  <div class="container mx-auto grid grid-cols-3 items-center py-4 px-6">
    <!-- Left: Logo -->
    <div class="col-start-1">
      <a href="/" class="text-2xl font-serif text-indigo-800">Dubrovnik Coast</a>
    </div>

    <!-- Middle: Desktop Nav -->
    <nav class="col-start-2 hidden md:flex items-center justify-center space-x-6">
      <a href="/" class="hover:text-indigo-600">Home</a>

      <!-- Properties Dropdown -->
      <div class="relative group">
        <button class="inline-flex items-center hover:text-indigo-600 focus:outline-none">
          <span>Properties</span>
          <svg class="ml-1 h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <!-- Dropdown Menu -->
        <div
          class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-44
                 bg-white shadow-lg rounded-md overflow-hidden
                 opacity-0 group-hover:opacity-100 transition-opacity"
        >
          <a
            href="/properties/#tab-villas"
            class="block px-4 py-2 hover:bg-gray-100 hover:text-indigo-600 transition-colors first:rounded-t-md"
          >Villas</a>
          <a
            href="/properties/#tab-apts"
            class="block px-4 py-2 hover:bg-gray-100 hover:text-indigo-600 transition-colors last:rounded-b-md"
          >Apartments</a>
        </div>
      </div>

      <a href="#about" class="hover:text-indigo-600">Our Story</a>
      <a href="/blog" class="hover:text-indigo-600">Blog</a>
      <a href="/contact" class="hover:text-indigo-600">Contact</a>
    </nav>

    <!-- Right: Book Now + Mobile Toggle -->
    <div class="col-start-3 flex justify-end items-center space-x-4">
      <a href="/properties/"
         class="hidden md:inline-block bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
        Book Now
      </a>
      <button id="mobile-menu-button" class="md:hidden focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-6 w-6 text-gray-700"
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
    </div>
  </div>

  <!-- Mobile Nav -->
  <nav id="mobile-menu" class="md:hidden hidden bg-white shadow-md">
    <ul class="flex flex-col py-2 space-y-1 px-4">
      <li><a href="/" class="block py-2 hover:text-indigo-600">Home</a></li>
      <li><a href="/properties/#tab-villas" class="block py-2 hover:text-indigo-600">Villas</a></li>
      <li><a href="/properties/#tab-apts" class="block py-2 hover:text-indigo-600">Apartments</a></li>
      <li><a href="#about" class="block py-2 hover:text-indigo-600">Our Story</a></li>
      <li><a href="/blog" class="block py-2 hover:text-indigo-600">Blog</a></li>
      <li><a href="/contact" class="block py-2 hover:text-indigo-600">Contact</a></li>
      <li>
        <a href="/properties/"
           class="block bg-indigo-600 text-white text-center py-2 rounded-lg hover:bg-indigo-700 transition">
          Book Now
        </a>
      </li>
    </ul>
  </nav>
</header>
<div class="h-20"></div>

<!-- ========== BREADCRUMB ========== -->
<nav class="container mx-auto px-6 text-sm text-gray-600 mb-6">
  <ol class="list-reset flex">
    <li><a href="/" class="hover:underline">Home</a></li>
    <li><span class="mx-2">/</span></li>
    <li class="text-gray-800">All Properties</li>
  </ol>
</nav>

<!-- ========== PAGE INTRO ========== -->
<section class="container mx-auto px-6 text-center mb-8">
  <h1 class="text-4xl font-serif text-indigo-800 mb-2">All Properties</h1>
  <p class="text-gray-600">Browse our full selection of villas and apartments.</p>
</section>

<!-- ========== TABS ========== -->
<div class="container mx-auto px-6 mb-8">
  <div class="flex justify-center space-x-4">
    <button id="tab-all" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">All</button>
    <button id="tab-villas" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">Villas</button>
    <button id="tab-apts" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">Apartments</button>
  </div>
</div>

<!-- ========== PROPERTIES GRID ========== -->
<section id="properties" class="container mx-auto px-6 flex-grow mb-16">
  <div class="grid gap-8 md:grid-cols-3 lg:grid-cols-4">
    <?php if ($catalogError): ?>
      <div class="col-span-full text-center text-gray-600">
        <?= htmlspecialchars($catalogError, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php else: ?>
      <?php foreach ($allProperties as $property): ?>
        <?php
          $imageSrc = catalog_image_src($property);
          if ($imageSrc === '') {
              continue;
          }
          $altText = trim((string) ($property['hero_alt'] ?? ''));
          if ($altText === '') {
              $altText = (string) ($property['name'] ?? '');
          }
          $highlightText = build_catalog_highlight($property);
          $url = $property['url'] ?? ('/properties/' . ($property['slug'] ?? '') . '/');
          $category = $property['category'] ?? 'other';
        ?>
        <div class="property-card border rounded-lg overflow-hidden shadow-lg flex flex-col"
             data-type="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>">
          <img src="<?= htmlspecialchars($imageSrc, ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($altText, ENT_QUOTES, 'UTF-8') ?>"
               class="w-full h-48 object-cover">
          <div class="p-4 flex flex-col h-full">
            <h3 class="text-xl font-semibold mb-1">
              <?= htmlspecialchars($property['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </h3>
            <?php if ($highlightText !== ''): ?>
              <p class="text-gray-600 text-sm mb-2"><?= $highlightText ?></p>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
               class="mt-auto text-indigo-600 hover:underline font-medium">
              View Details &rarr;
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<!-- ========== FOOTER ========== -->
<footer class="mt-auto bg-gray-800 text-gray-200">
  <div class="container mx-auto px-6 grid md:grid-cols-4 gap-8 py-12">
    <!-- Quick Links -->
    <div>
      <h3 class="font-serif text-lg text-white mb-4">Quick Links</h3>
      <ul class="space-y-2">
        <li><a href="#properties" class="hover:underline">Properties</a></li>
        <li><a href="#about" class="hover:underline">Our Story</a></li>
        <li><a href="#blog" class="hover:underline">Blog</a></li>
        <li><a href="#contact" class="hover:underline">FAQs</a></li>
        <li><a href="#contact" class="hover:underline">Contact</a></li>
      </ul>
    </div>

    <!-- Legal Links -->
    <div>
      <h3 class="font-serif text-lg text-white mb-4">Legal</h3>
      <ul class="space-y-2">
        <li><a href="/terms" class="hover:underline">Terms &amp; Conditions</a></li>
        <li><a href="/privacy" class="hover:underline">Privacy Policy</a></li>
        <li><a href="/cookies" class="hover:underline">Cookie Policy</a></li>
        <li><a href="/disclaimer" class="hover:underline">Disclaimer</a></li>
        <li><a href="/accessibility" class="hover:underline">Accessibility</a></li>
      </ul>
    </div>

    <!-- Connect & Social -->
    <div>
      <h3 class="font-serif text-lg text-white mb-4">Connect With Us</h3>
      <div class="flex space-x-4 mb-4">
        <a href="https://www.facebook.com/dubrovnikcoast" target="_blank" rel="noopener noreferrer" class="hover:text-white">
          <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24">
            <path d="M22.675 0H1.325C.593 0 0 .593 0 1.325v21.351C0 23.407.593 24 1.325 24H12.82V14.708h-3.07v-3.62h3.07V8.413c0-3.04 1.855-4.696 4.566-4.696 1.297 0 2.41.097 2.734.14v3.17l-1.875.001c-1.472 0-1.757.7-1.757 1.725v2.267h3.516l-.458 3.62h-3.058V24h6.005C23.407 24 24 23.407 24 22.676V1.325C24 .593 23.407 0 22.675 0z"/>
          </svg>
        </a>
        <a href="https://www.instagram.com/dubrovnikcoast" target="_blank" rel="noopener noreferrer" class="hover:text-white">
          <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24">
            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.206.058 2.003.248 2.473.415a4.918 4.918 0 011.688 1.093 4.918 4.918 0 011.093 1.688c.167.47.357 1.267.415 2.473.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.058 1.206-.248 2.003-.415 2.473a4.92 4.92 0 01-1.093 1.688 4.92 4.92 0 01-1.688 1.093c-.47.167-1.267.357-2.473.415-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.206-.058-2.003-.248-2.473-.415a4.918 4.918 0 01-1.688-1.093 4.918 4.918 0 01-1.093-1.688c-.167-.47-.357-1.267-.415-2.473C2.175 15.747 2.163 15.367 2.163 12s.012-3.584.07-4.85c.058-1.206.248-2.003.415-2.473a4.918 4.918 0 011.093-1.688A4.918 4.918 0 015.359 2.578c.47-.167 1.267-.357 2.473-.415C8.416 2.175 8.796 2.163 12 2.163zm0-2.163C8.741 0 8.332.012 7.052.07 5.778.127 4.668.316 3.707.706a7.075 7.075 0 00-2.579 1.637A7.075 7.075 0 00.706 4.706C.316 5.667.127 6.777.07 8.051.012 9.332 0 9.741 0 12s.012 2.668.07 3.948c.057 1.274.246 2.384.636 3.345a7.075 7.075 0 001.637 2.579 7.075 7.075 0 002.579 1.637c.961.39 2.071.579 3.345.636 1.28.058 1.689.07 3.948.07s2.668-.012 3.948-.07c1.274-.057 2.384-.246 3.345-.636a7.075 7.075 0 002.579-1.637 7.075 7.075 0 001.637-2.579c.39-.961.579-2.071.636-3.345.058-1.28.07-1.689.07-3.948s-.012-2.668-.07-3.948c-.057-1.274-.246-2.384-.636-3.345a7.075 7.075 0 00-1.637-2.579A7.075 7.075 0 0020.293.706C19.332.316 18.222.127 16.948.07 15.668.012 15.259 0 12 0z"/>
            <circle cx="12" cy="12" r="3.2"/>
            <path d="M18.406 5.594a1.44 1.44 0 11-2.876 0 1.44 1.44 0 012.876 0z"/>
          </svg>
        </a>
      </div>
      <p class="text-gray-400 font-medium">Follow us for exclusive offers &amp; inspiration</p>
    </div>

    <!-- Payment Methods -->
    <div>
      <h3 class="font-serif text-lg text-white mb-4">Secure Payments</h3>
      <div class="flex space-x-4">
        <img src="/img/payments/stripefooter.png" alt="Stripe" class="h-8 w-auto" />
      </div>
    </div>
  </div>

  <!-- Subfooter -->
  <div class="bg-gray-900 text-gray-500 py-4">
    <div class="container mx-auto px-6 text-center text-sm">
      &copy; 2025 Dubrovnik Coast. All rights reserved.
    </div>
  </div>
</footer>

<!-- ========== SCRIPTS ========== -->
<script src="/main.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const allBtn = document.getElementById('tab-all');
    const villasBtn = document.getElementById('tab-villas');
    const aptsBtn = document.getElementById('tab-apts');
    const cards = document.querySelectorAll('.property-card');

    function activate(btn) {
      [allBtn, villasBtn, aptsBtn].forEach(b => {
        b.classList.toggle('bg-indigo-600', b === btn);
        b.classList.toggle('text-white', b === btn);
        b.classList.toggle('bg-gray-200', b !== btn);
        b.classList.toggle('text-gray-700', b !== btn);
      });
    }

    function filter(type) {
      cards.forEach(card => {
        card.classList.toggle(
          'hidden',
          type !== 'all' && card.dataset.type !== type
        );
      });
    }

    function switchTab(btn, type, hash) {
      activate(btn);
      filter(type);
      history.replaceState(null, '', hash);
    }

    allBtn.addEventListener('click', () => switchTab(allBtn, 'all', '#tab-all'));
    villasBtn.addEventListener('click', () => switchTab(villasBtn, 'villa', '#tab-villas'));
    aptsBtn.addEventListener('click', () => switchTab(aptsBtn, 'apartment', '#tab-apts'));

    const currentHash = window.location.hash;
    if (currentHash === '#tab-villas') {
      villasBtn.click();
    } else if (currentHash === '#tab-apts') {
      aptsBtn.click();
    } else {
      allBtn.click();
    }
  });
</script>
</body>
</html>
