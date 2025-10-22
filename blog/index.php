<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/blog.php';

$data = fetch_blog_index_data();
$posts = $data['posts'];
$categories = $data['categories'];
$tags = $data['tags'];

$pageTitle = 'Dubrovnik Coast Blog';
$pageDescription = 'Travel inspiration, insider tips, and curated experiences to help you plan your stay along the Dubrovnik coast.';

$blogPostsSchema = array_slice(array_map(static function (array $post): array {
    $articleUrl = SITE_BASE_URL . '/blog/' . $post['slug'] . '/';
    $schema = [
        '@type' => 'BlogPosting',
        'headline' => $post['title'],
        'url' => $articleUrl,
        'datePublished' => date('c', strtotime((string) $post['published_at'])),
        'description' => $post['excerpt'] ?? '',
    ];
    if (!empty($post['featured_image'])) {
        $schema['image'] = SITE_BASE_URL . $post['featured_image'];
    }
    return $schema;
}, $posts), 0, 5);

$blogSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Blog',
    'name' => 'Dubrovnik Coast Blog',
    'description' => $pageDescription,
    'url' => SITE_BASE_URL . '/blog/',
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Dubrovnik Coast',
        'url' => SITE_BASE_URL,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => SITE_BASE_URL . '/img/logo.png',
        ],
    ],
    'blogPost' => array_values(array_filter($blogPostsSchema)),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($pageTitle) ?> &mdash; Dubrovnik Coast</title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html { scroll-behavior: smooth; }
  </style>
  <script type="application/ld+json">
    <?= json_encode($blogSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
  </script>
</head>
<body class="flex flex-col min-h-screen font-sans text-gray-800">

<!-- HEADER -->
<header class="bg-white shadow-md fixed w-full z-20">
  <div class="container mx-auto grid grid-cols-3 items-center py-4 px-6">
    <div class="col-start-1">
      <a href="/" class="text-2xl font-serif text-indigo-800">Dubrovnik Coast</a>
    </div>
    <nav class="col-start-2 hidden md:flex items-center justify-center space-x-6">
      <a href="/" class="hover:text-indigo-600">Home</a>
      <div class="relative group">
        <button class="inline-flex items-center hover:text-indigo-600 focus:outline-none">
          <span>Properties</span>
          <svg class="ml-1 h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div
          class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-44
                 bg-white shadow-lg rounded-md overflow-hidden
                 opacity-0 group-hover:opacity-100 transition-opacity">
          <a href="/properties/#tab-villas" class="block px-4 py-2 hover:bg-gray-100 hover:text-indigo-600 transition-colors first:rounded-t-md">Villas</a>
          <a href="/properties/#tab-apts" class="block px-4 py-2 hover:bg-gray-100 hover:text-indigo-600 transition-colors last:rounded-b-md">Apartments</a>
        </div>
      </div>
      <a href="/our-story" class="hover:text-indigo-600">Our Story</a>
      <a href="/blog/" class="text-indigo-600 font-semibold">Blog</a>
      <a href="/contact" class="hover:text-indigo-600">Contact</a>
    </nav>
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
  <nav id="mobile-menu" class="md:hidden hidden bg-white shadow-md">
    <ul class="flex flex-col py-2 space-y-1 px-4">
      <li><a href="/" class="block py-2 hover:text-indigo-600">Home</a></li>
      <li><a href="/properties/#tab-villas" class="block py-2 hover:text-indigo-600">Villas</a></li>
      <li><a href="/properties/#tab-apts" class="block py-2 hover:text-indigo-600">Apartments</a></li>
      <li><a href="/our-story" class="block py-2 hover:text-indigo-600">Our Story</a></li>
      <li><a href="/blog/" class="block py-2 text-indigo-600 font-semibold">Blog</a></li>
      <li><a href="/contact" class="block py-2 hover:text-indigo-600">Contact</a></li>
      <li>
        <a href="/properties/" class="block bg-indigo-600 text-white text-center py-2 rounded-lg hover:bg-indigo-700 transition">
          Book Now
        </a>
      </li>
    </ul>
  </nav>
</header>

<div class="h-20"></div>

<main class="container mx-auto px-6 py-12 flex-grow">
  <div class="grid lg:grid-cols-[2fr_1fr] gap-12">
    <section class="space-y-8">
      <header class="space-y-3">
        <p class="text-sm uppercase tracking-[0.3em] text-indigo-600">Dubrovnik Coast Blog</p>
        <h1 class="text-4xl font-serif text-indigo-900">Stories from the Adriatic</h1>
        <p class="text-gray-600 max-w-2xl">Discover curated itineraries, local culture, seasonal guides, and luxury travel tips to help you plan your Dubrovnik escape.</p>
      </header>

      <?php if (empty($posts)): ?>
        <div class="bg-white border border-dashed border-gray-200 rounded-xl p-10 text-center text-gray-500">
          <p class="text-lg font-medium mb-2">New stories are coming soon.</p>
          <p>Check back shortly for travel insights and insider tips.</p>
        </div>
      <?php else: ?>
        <div id="blog-grid" class="grid gap-8">
          <?php foreach ($posts as $post): ?>
            <article class="blog-card bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm transition hover:shadow-lg"
                     data-categories="<?= htmlspecialchars(implode(' ', $post['category_slugs'])) ?>"
                     data-tags="<?= htmlspecialchars(implode(' ', $post['tag_slugs'])) ?>">
              <?php if (!empty($post['featured_image'])): ?>
                <a href="/blog/<?= htmlspecialchars($post['slug']) ?>/" class="block">
                  <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['featured_alt'] ?? $post['title']) ?>" class="w-full h-64 object-cover">
                </a>
              <?php endif; ?>
              <div class="p-6 space-y-4">
                <div class="flex items-center gap-3 text-xs text-indigo-600">
                  <time datetime="<?= htmlspecialchars(date('Y-m-d', strtotime((string) $post['published_at']))) ?>">
                    <?= htmlspecialchars(date('F j, Y', strtotime((string) $post['published_at']))) ?>
                  </time>
                  <?php if (!empty($post['reading_time'])): ?>
                    <span>&bull; <?= (int) $post['reading_time'] ?> min read</span>
                  <?php endif; ?>
                </div>
                <h2 class="text-2xl font-serif text-indigo-900">
                  <a href="/blog/<?= htmlspecialchars($post['slug']) ?>/"><?= htmlspecialchars($post['title']) ?></a>
                </h2>
                <?php if (!empty($post['category_names'])): ?>
                  <div class="flex flex-wrap gap-2 text-xs">
                    <?php foreach ($post['category_names'] as $index => $name): ?>
                      <span class="uppercase tracking-wide text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full">
                        <?= htmlspecialchars($name) ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <?php if (!empty($post['excerpt'])): ?>
                  <p class="text-gray-600"><?= htmlspecialchars($post['excerpt']) ?></p>
                <?php endif; ?>
                <div>
                  <a href="/blog/<?= htmlspecialchars($post['slug']) ?>/" class="inline-flex items-center gap-2 text-indigo-600 font-medium hover:text-indigo-700">
                    Read story
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <aside class="space-y-8">
      <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-indigo-900">Filter by category</h2>
          <button type="button" id="clear-categories" class="text-xs text-indigo-600 hover:underline">Clear</button>
        </div>
        <?php if (empty($categories)): ?>
          <p class="text-sm text-gray-500">Categories help readers browse themes. Add some in the admin panel.</p>
        <?php else: ?>
          <div class="flex flex-wrap gap-2" id="category-filter">
            <?php foreach ($categories as $category): ?>
              <button type="button"
                      class="filter-chip px-3 py-1 text-sm border border-indigo-200 text-indigo-700 rounded-full hover:bg-indigo-50 transition"
                      data-filter-type="category"
                      data-filter-slug="<?= htmlspecialchars($category['slug']) ?>">
                <?= htmlspecialchars($category['name']) ?>
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-indigo-900">Filter by tag</h2>
          <button type="button" id="clear-tags" class="text-xs text-indigo-600 hover:underline">Clear</button>
        </div>
        <?php if (empty($tags)): ?>
          <p class="text-sm text-gray-500">Tags highlight key travel themes. Add some in the admin panel.</p>
        <?php else: ?>
          <div class="flex flex-wrap gap-2" id="tag-filter">
            <?php foreach ($tags as $tag): ?>
              <button type="button"
                      class="filter-chip px-3 py-1 text-sm border border-indigo-200 text-indigo-700 rounded-full hover:bg-indigo-50 transition"
                      data-filter-type="tag"
                      data-filter-slug="<?= htmlspecialchars($tag['slug']) ?>">
                <?= htmlspecialchars($tag['name']) ?>
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</main>

<!-- FOOTER -->
<footer class="mt-auto bg-gray-800 text-gray-200">
  <div class="container mx-auto px-6 grid md:grid-cols-4 gap-8 py-12">
    <div>
      <h3 class="font-serif text-lg text-white mb-4">Quick Links</h3>
      <ul class="space-y-2">
        <li><a href="/properties/" class="hover:underline">Properties</a></li>
        <li><a href="/our-story" class="hover:underline">Our Story</a></li>
        <li><a href="/blog/" class="hover:underline">Blog</a></li>
        <li><a href="/contact" class="hover:underline">FAQs</a></li>
        <li><a href="/contact" class="hover:underline">Contact</a></li>
      </ul>
    </div>
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
    <div>
      <h3 class="font-serif text-lg text-white mb-4">Secure Payments</h3>
      <div class="flex space-x-4">
        <img src="/img/payments/stripefooter.png" alt="Stripe" class="h-8 w-auto" />
      </div>
    </div>
  </div>
  <div class="bg-gray-900 text-gray-500 py-4">
    <div class="container mx-auto px-6 text-center text-sm">
      &copy; 2025 Dubrovnik Coast. All rights reserved.
    </div>
  </div>
</footer>

<script>
  (function () {
    const mobileToggle = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileToggle && mobileMenu) {
      mobileToggle.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
      });
    }

    const activeCategories = new Set();
    const activeTags = new Set();
    const cards = Array.from(document.querySelectorAll('.blog-card'));

    function applyFilters() {
      cards.forEach(card => {
        const cardCategories = (card.dataset.categories || '').split(' ').filter(Boolean);
        const cardTags = (card.dataset.tags || '').split(' ').filter(Boolean);

        const matchesCategories = activeCategories.size === 0 || Array.from(activeCategories).every(slug => cardCategories.includes(slug));
        const matchesTags = activeTags.size === 0 || Array.from(activeTags).every(slug => cardTags.includes(slug));

        card.classList.toggle('hidden', !(matchesCategories && matchesTags));
      });
    }

    function toggleFilter(button, collection) {
      const slug = button.dataset.filterSlug;
      if (!slug) {
        return;
      }
      if (collection.has(slug)) {
        collection.delete(slug);
        button.classList.remove('bg-indigo-600', 'text-white');
        button.classList.add('border-indigo-200', 'text-indigo-700');
      } else {
        collection.add(slug);
        button.classList.add('bg-indigo-600', 'text-white');
        button.classList.remove('border-indigo-200', 'text-indigo-700');
      }
      applyFilters();
    }

    document.querySelectorAll('.filter-chip').forEach(button => {
      button.addEventListener('click', () => {
        const type = button.dataset.filterType;
        if (type === 'category') {
          toggleFilter(button, activeCategories);
        } else if (type === 'tag') {
          toggleFilter(button, activeTags);
        }
      });
    });

    function clearFilters(collection, selector) {
      collection.clear();
      document.querySelectorAll(selector).forEach(button => {
        button.classList.remove('bg-indigo-600', 'text-white');
        button.classList.add('border-indigo-200', 'text-indigo-700');
      });
      applyFilters();
    }

    const clearCategories = document.getElementById('clear-categories');
    if (clearCategories) {
      clearCategories.addEventListener('click', () => clearFilters(activeCategories, '[data-filter-type="category"]'));
    }

    const clearTags = document.getElementById('clear-tags');
    if (clearTags) {
      clearTags.addEventListener('click', () => clearFilters(activeTags, '[data-filter-type="tag"]'));
    }
  })();
</script>
</body>
</html>
