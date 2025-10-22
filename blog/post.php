<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/blog.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: /blog/');
    exit;
}

$post = fetch_blog_post_by_slug($slug);

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Article Not Found';
    $related = [];
} else {
    $pageTitle = $post['title'];
    $related = fetch_additional_blog_posts([$post['id']]);
}

$metaDescription = $post['meta_description'] ?? ($post['excerpt'] ?? '');

$schema = null;
if ($post) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => SITE_BASE_URL . '/blog/' . $post['slug'] . '/',
        ],
        'headline' => $post['title'],
        'description' => $post['meta_description'] ?? $post['excerpt'] ?? '',
        'datePublished' => date('c', strtotime((string) $post['published_at'])),
        'dateModified' => date('c', strtotime((string) $post['updated_at'] ?? $post['published_at'])),
        'author' => [
            '@type' => 'Organization',
            'name' => 'Dubrovnik Coast',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Dubrovnik Coast',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => SITE_BASE_URL . '/img/logo.png',
            ],
        ],
    ];
    if (!empty($post['featured_image'])) {
        $schema['image'] = [
            '@type' => 'ImageObject',
            'url' => SITE_BASE_URL . $post['featured_image'],
        ];
    }
    if (!empty($post['canonical_url'])) {
        $schema['mainEntityOfPage']['@id'] = $post['canonical_url'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($pageTitle) ?> &mdash; Dubrovnik Coast Blog</title>
  <?php if ($post && !empty($metaDescription)): ?>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>" />
  <?php endif; ?>
  <?php if ($post && !empty($post['canonical_url'])): ?>
    <link rel="canonical" href="<?= htmlspecialchars($post['canonical_url']) ?>" />
  <?php else: ?>
    <link rel="canonical" href="<?= htmlspecialchars(SITE_BASE_URL . '/blog/' . $slug . '/') ?>" />
  <?php endif; ?>
  <?php if ($post): ?>
    <?php if (!empty($post['meta_title'])): ?>
      <meta property="og:title" content="<?= htmlspecialchars($post['meta_title']) ?>" />
    <?php else: ?>
      <meta property="og:title" content="<?= htmlspecialchars($post['title']) ?>" />
    <?php endif; ?>
    <?php if (!empty($metaDescription)): ?>
      <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>" />
    <?php endif; ?>
    <meta property="og:type" content="article" />
    <meta property="og:url" content="<?= htmlspecialchars(SITE_BASE_URL . '/blog/' . $post['slug'] . '/') ?>" />
    <?php if (!empty($post['featured_image'])): ?>
      <meta property="og:image" content="<?= htmlspecialchars(SITE_BASE_URL . $post['featured_image']) ?>" />
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= htmlspecialchars($post['meta_title'] ?? $post['title']) ?>" />
    <?php if (!empty($metaDescription)): ?>
      <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>" />
    <?php endif; ?>
    <?php if (!empty($post['featured_image'])): ?>
      <meta name="twitter:image" content="<?= htmlspecialchars(SITE_BASE_URL . $post['featured_image']) ?>" />
    <?php endif; ?>
  <?php endif; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html { scroll-behavior: smooth; }
    .blog-content h2 { font-size: 1.5rem; margin-top: 1.5rem; margin-bottom: 1rem; }
    .blog-content h3 { font-size: 1.25rem; margin-top: 1.25rem; margin-bottom: 0.75rem; }
    .blog-content p { margin-bottom: 1rem; line-height: 1.75; }
    .blog-content ul, .blog-content ol { margin-left: 1.5rem; margin-bottom: 1rem; }
    .blog-content img { border-radius: 0.75rem; margin: 1.5rem 0; }
    .blog-content blockquote { border-left: 4px solid #4f46e5; padding-left: 1.25rem; color: #4a5568; font-style: italic; margin: 1.5rem 0; }
  </style>
  <?php if ($schema): ?>
    <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?></script>
  <?php endif; ?>
</head>
<body class="flex flex-col min-h-screen font-sans text-gray-800">

<!-- shared header (same as blog index) -->
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
  <?php if (!$post): ?>
      <section class="max-w-3xl mx-auto text-center space-y-4">
          <h1 class="text-4xl font-serif text-indigo-900">Article not found</h1>
          <p class="text-gray-600">The story you are looking for is no longer available. Explore other travel inspiration below.</p>
          <a href="/blog/" class="inline-flex items-center bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
              Back to Blog
          </a>
      </section>
  <?php else: ?>
    <article class="max-w-4xl mx-auto space-y-10">
      <header class="space-y-4 text-center">
        <p class="text-xs uppercase tracking-[0.3em] text-indigo-600">Dubrovnik Coast Blog</p>
        <h1 class="text-4xl md:text-5xl font-serif text-indigo-900"><?= htmlspecialchars($post['title']) ?></h1>
        <div class="flex flex-wrap justify-center items-center gap-3 text-sm text-gray-500">
          <time datetime="<?= htmlspecialchars(date('Y-m-d', strtotime((string) $post['published_at']))) ?>">
            <?= htmlspecialchars(date('F j, Y', strtotime((string) $post['published_at']))) ?>
          </time>
          <?php if (!empty($post['reading_time'])): ?>
            <span>&bull; <?= (int) $post['reading_time'] ?> min read</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($post['category_names'])): ?>
          <div class="flex flex-wrap justify-center gap-2">
            <?php foreach ($post['category_names'] as $name): ?>
              <span class="uppercase tracking-wide text-xs text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full"><?= htmlspecialchars($name) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </header>

      <?php if (!empty($post['featured_image'])): ?>
        <figure class="overflow-hidden rounded-3xl shadow-lg">
          <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['featured_alt'] ?? $post['title']) ?>" class="w-full object-cover">
        </figure>
      <?php endif; ?>

      <div class="blog-content prose prose-lg max-w-none">
        <?= $post['content'] ?>
      </div>

      <?php if (!empty($post['tag_names'])): ?>
        <section class="flex flex-wrap gap-2">
          <?php foreach ($post['tag_names'] as $name): ?>
            <span class="text-sm text-indigo-700 bg-indigo-50 px-3 py-1 rounded-full">#<?= htmlspecialchars($name) ?></span>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>
    </article>
  <?php endif; ?>

  <?php if (!empty($related)): ?>
    <section class="max-w-6xl mx-auto mt-16">
      <header class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-serif text-indigo-900">You may also like</h2>
        <a href="/blog/" class="text-sm text-indigo-600 hover:underline">View all stories</a>
      </header>
      <div class="grid md:grid-cols-3 gap-8">
        <?php foreach ($related as $item): ?>
          <article class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition">
            <?php if (!empty($item['featured_image'])): ?>
              <a href="/blog/<?= htmlspecialchars($item['slug']) ?>/" class="block">
                <img src="<?= htmlspecialchars($item['featured_image']) ?>" alt="<?= htmlspecialchars($item['featured_alt'] ?? $item['title']) ?>" class="w-full h-48 object-cover">
              </a>
            <?php endif; ?>
            <div class="p-5 space-y-3">
              <div class="text-xs text-indigo-600">
                <?= htmlspecialchars(date('F j, Y', strtotime((string) $item['published_at']))) ?>
              </div>
              <h3 class="text-xl font-semibold text-indigo-900">
                <a href="/blog/<?= htmlspecialchars($item['slug']) ?>/"><?= htmlspecialchars($item['title']) ?></a>
              </h3>
              <?php if (!empty($item['excerpt'])): ?>
                <p class="text-sm text-gray-600"><?= htmlspecialchars($item['excerpt']) ?></p>
              <?php endif; ?>
              <a href="/blog/<?= htmlspecialchars($item['slug']) ?>/" class="inline-flex items-center gap-2 text-indigo-600 font-medium hover:text-indigo-700">
                Read story
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>

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
  })();
</script>
</body>
</html>
