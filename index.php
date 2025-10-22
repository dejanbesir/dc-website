<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/homepage.php';

$featuredSlides = fetch_featured_property_slides();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dubrovnik Coast • Luxury Villas & Apartments</title>
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Optional: Add your own Tailwind configuration or custom CSS here -->
  <style>
    /* Example: Override default scroll behavior for smooth scrolling */
    html {
      scroll-behavior: smooth;
    }
  </style>
    <!-- Basic Organization + WebSite Schema -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "TravelAgency",
    "name": "Dubrovnik Coast",
    "url": "https://www.dubrovnik-coast.com",
    "logo": "https://www.dubrovnik-coast.com/img/logo.png",
    "sameAs": [
      "https://www.facebook.com/dubrovnikcoast",
      "https://www.instagram.com/dubrovnikcoast"
    ],
    "contactPoint": {
      "@type": "ContactPoint",
      "telephone": "+385-20-123-456",
      "contactType": "customer service",
      "areaServed": "HR",
      "availableLanguage": ["English", "Croatian"]
    }
  }
  </script>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "url": "https://www.dubrovnik-coast.com",
    "potentialAction": {
      "@type": "SearchAction",
      "target": "https://www.dubrovnikcoast.com/search?q={search_term_string}",
      "query-input": "required name=search_term_string"
    }
  }
  </script>
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


  <!-- Spacer to offset fixed header -->
  <div class="h-20"></div>

  <!-- ========== HERO SECTION ========== -->
  <section
    class="relative bg-cover bg-center h-screen"
    style="background-image: url('/img/dc-bghero.jpg');">
    <div class="absolute inset-0 bg-black bg-opacity-40"></div>
    <div class="container mx-auto relative z-10 flex flex-col items-center justify-center h-full text-center px-6">
      <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif text-white leading-tight mb-4">
        Escape to the Heart of the Adriatic
      </h1>
      <p class="text-lg md:text-xl text-gray-200 mb-8 max-w-2xl">
        Discover hand-picked villas and apartments along the stunning Dubrovnik coastline.
      </p>
      <a href="#properties"
        class="bg-indigo-600 text-white font-semibold px-6 py-3 rounded-lg shadow-lg hover:bg-indigo-700 transition">
        Book Now
      </a>
    </div>
  </section>


<!-- ========== WHY BOOK DIRECT SECTION ========== -->
<section class="py-16 bg-gray-50">
  <div class="container mx-auto px-6 text-center">
    <h2 class="text-3xl font-serif text-indigo-800 mb-8">
      Why Book Direct with Dubrovnik Coast
    </h2>
    <div class="grid md:grid-cols-3 lg:grid-cols-4 gap-8">

      <!-- Best Rates Guaranteed -->
      <div class="flex flex-col items-center space-y-3">
        <div class="h-12 flex items-center justify-center">
          <!-- Percent Icon -->
          <svg xmlns="http://www.w3.org/2000/svg"
               class="h-12 w-12 text-indigo-600" fill="none"
               viewBox="0 0 24 24" stroke="currentColor">
            <circle cx="6" cy="6" r="2" stroke="currentColor" stroke-width="2"/>
            <circle cx="18" cy="18" r="2" stroke="currentColor" stroke-width="2"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18"/>
          </svg>
        </div>
        <h3 class="text-xl font-semibold">Best Rates Guaranteed</h3>
        <p class="text-gray-600">
          Book direct with the owner—no OTA fees or hidden markups. Always the lowest price, guaranteed.
        </p>
      </div>

      <!-- Trusted Local Experts -->
      <div class="flex flex-col items-center space-y-3">
        <div class="h-12 flex items-center justify-center">
          <!-- Map Pin Icon -->
          <svg xmlns="http://www.w3.org/2000/svg"
               class="h-12 w-12 text-indigo-600" fill="none"
               viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 
                     9 6.343 9 8s1.343 3 3 3z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 22s8-7.5 8-12a8 8 0 10-16 0
                     c0 4.5 8 12 8 12z"/>
          </svg>
        </div>
        <h3 class="text-xl font-semibold">Trusted Local Experts</h3>
        <p class="text-gray-600">
          Our local team hand-selects every property and curates insider tips—so you experience Dubrovnik like a true insider.
        </p>
      </div>

      <!-- Personalized 24/7 Concierge -->
      <div class="flex flex-col items-center space-y-3">
        <div class="h-12 flex items-center justify-center">
          <!-- Bell Icon -->
          <svg xmlns="http://www.w3.org/2000/svg"
               class="h-12 w-12 text-indigo-600" fill="none"
               viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11
                     a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5
                     m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
          </svg>
        </div>
        <h3 class="text-xl font-semibold">Personalized 24/7 Concierge</h3>
        <p class="text-gray-600">
          From private tours and bespoke activities to last-minute requests, our team is at your service—day or night.
        </p>
      </div>

      <!-- Secure Payments -->
      <div class="flex flex-col items-center space-y-3">
        <div class="h-12 flex items-center justify-center">
          <!-- Shield Check Icon -->
          <svg xmlns="http://www.w3.org/2000/svg"
               class="h-12 w-12 text-indigo-600" fill="none"
               viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 22s8-7.5 8-12a8 8 0 10-16 0
                     c0 4.5 8 12 8 12z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4"/>
          </svg>
        </div>
        <h3 class="text-xl font-semibold">Secure Payments</h3>
        <p class="text-gray-600">
          Your data is safe—PCI-compliant checkout for peace of mind on every booking.
        </p>
      </div>

    </div>
  </div>
</section>



  <!-- ========== FEATURED VILLAS & APARTMENTS CAROUSEL ========== -->
  <section id="properties" class="py-16 bg-white">
    <div class="container mx-auto px-6 text-center">
      <h2 class="text-3xl font-serif text-indigo-800 mb-8">
        Featured Villas & Apartments
      </h2>
      <div class="relative max-w-4xl mx-auto">
        <!-- Slides Container -->
        <div id="carousel" class="overflow-hidden rounded-lg shadow-lg">
          <?php if (!empty($featuredSlides)): ?>
            <?php foreach ($featuredSlides as $slideIndex => $slide): ?>
              <?php $isActive = $slideIndex === 0; ?>
              <?php $columnClass = count($slide['properties']) > 1 ? ' md:grid-cols-2' : ''; ?>
              <div
                class="carousel-item flex-shrink-0 w-full<?= $isActive ? '' : ' hidden' ?>"
                data-index="<?= $slideIndex ?>"
                <?php if (!$isActive): ?>aria-hidden="true"<?php endif; ?>
              >
                <div class="grid gap-6<?= $columnClass ?>">
                  <?php foreach ($slide['properties'] as $property): ?>
                    <article class="flex flex-col text-left bg-white">
                      <img
                        src="<?= htmlspecialchars($property['hero_image'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($property['hero_alt'] !== '' ? $property['hero_alt'] : $property['name'], ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full h-64 object-cover"
                        loading="lazy"
                      />
                      <div class="p-6 text-left flex flex-col flex-grow">
                        <h3 class="text-2xl font-semibold mb-2"><?= htmlspecialchars($property['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <?php if (!empty($property['highlights'])): ?>
                          <p class="text-gray-600 mb-2">
                            <?php foreach ($property['highlights'] as $idx => $highlight): ?>
                              <?php if ($idx > 0): ?>
                                <span class="mx-2 text-gray-400">&bull;</span>
                              <?php endif; ?>
                              <span><?= htmlspecialchars($highlight, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                          </p>
                        <?php endif; ?>
                        <?php if (!empty($property['price_label'])): ?>
                          <p class="font-semibold mb-4"><?= $property['price_label'] ?></p>
                        <?php endif; ?>
                        <div class="mt-auto">
                          <a
                            href="<?= htmlspecialchars($property['url'], ENT_QUOTES, 'UTF-8') ?>"
                            class="inline-block bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition"
                          >
                            View Details
                          </a>
                        </div>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="carousel-item flex-shrink-0 w-full" data-index="0">
              <div class="p-6 text-left">
                <h3 class="text-2xl font-semibold mb-2">Featured properties coming soon</h3>
                <p class="text-gray-600">
                  Our team is curating the latest villas and apartments. Please check back soon.
                </p>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Carousel Controls -->
        <button
          id="prevBtn"
          class="absolute z-10 left-2 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-75 hover:bg-opacity-100 rounded-full p-2 shadow-md"
          aria-label="Previous slide"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <button
          id="nextBtn"
          class="absolute z-10 right-2 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-75 hover:bg-opacity-100 rounded-full p-2 shadow-md"
          aria-label="Next slide"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <div class="mt-6">
        <a
          href="/properties"
          class="text-indigo-600 hover:underline font-medium"
        >
          Browse All Villas & Apartments →
        </a>
      </div>
    </div>
  </section>




  <!-- ========== GUEST TESTIMONIALS ========== -->
  <section class="py-16 bg-gray-50">
    <div class="container mx-auto px-6 text-center">
      <h2 class="text-3xl font-serif text-indigo-800 mb-8">Our Guests Love Us</h2>
      <div class="grid md:grid-cols-2 gap-8">
        <div class="bg-white p-6 shadow-lg rounded-lg">
          <p class="italic text-gray-700 mb-4">“From the moment we arrived, Dubrovnik Coast took care of everything. Our villa felt like home—and the private chef was a dream!”</p>
          <div class="flex items-center justify-center space-x-3">
            <div>
              <!-- Star Rating -->
              <span class="text-yellow-400">★★★★★</span>
            </div>
            <div class="text-gray-600 font-medium">— The Harris Family, London</div>
          </div>
        </div>
        <div class="bg-white p-6 shadow-lg rounded-lg">
          <p class="italic text-gray-700 mb-4">“Traveling for work, I needed a peaceful retreat with meeting space. Dubrovnik Coast arranged everything—from projectors to boat tours. Highly recommend.”</p>
          <div class="flex items-center justify-center space-x-3">
            <div>
              <span class="text-yellow-400">★★★★★</span>
            </div>
            <div class="text-gray-600 font-medium">— Marco R., Milan</div>
          </div>
        </div>
      </div>
      <div class="mt-6">
        <a href="#all-reviews" class="text-indigo-600 hover:underline font-medium">See More Reviews →</a>
      </div>
    </div>
  </section>

  <!-- ========== LOCAL EXPERTISE & CONCIERGE ========== -->
  <section id="about" class="py-16 bg-white">
    <div class="container mx-auto px-6 flex flex-col lg:flex-row items-center lg:space-x-12">
      <div class="lg:w-1/2 mb-8 lg:mb-0">
        <img src="/img/localexpertise.jpg" alt="Local Expertise" class="rounded-lg shadow-lg w-full object-cover h-80" />
      </div>
      <div class="lg:w-1/2">
        <h2 class="text-3xl font-serif text-indigo-800 mb-4">Local Expertise & Concierge</h2>
        <p class="text-gray-700 mb-4">
          When you book with Dubrovnik Coast, you don’t just reserve a villa—you gain a trusted local partner. Our team, born and raised on the Dalmatian coast, knows every hidden cove, konoba, and cultural secret. From:
        </p>
        <ul class="list-disc pl-5 space-y-2 text-gray-600 mb-6">
          <li>Sunset sailing along the Elaphiti Islands</li>
          <li>Private truffle-hunting excursions in Konavle</li>
          <li>In-villa yoga sessions with a certified instructor</li>
          <li>Wine tastings at historic vineyards near Ston</li>
        </ul>
        <a href="#services"
          class="bg-indigo-600 text-white px-5 py-3 rounded-lg hover:bg-indigo-700 transition font-medium">Learn More</a>
      </div>
    </div>
  </section>

  <!-- ========== BLOG CTA ========== -->
  <section id="blog" class="py-16 bg-gray-50">
    <div class="container mx-auto px-6 text-center">
      <h2 class="text-3xl font-serif text-indigo-800 mb-4">Get Inspired: Dubrovnik Insider Guide</h2>
      <p class="text-gray-700 mb-6">“From secret beaches to festive summer concerts—our blog is your gateway to Dubrovnik’s best-kept secrets.”</p>
      <a href="blog.html"
        class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition font-medium">Read Our Latest Articles</a>
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
            <!-- Facebook SVG -->
            <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24">
              <path d="M22.675 0H1.325C.593 0 0 .593 0 1.325v21.351C0 23.407.593 24 1.325 24H12.82V14.708h-3.07v-3.62h3.07V8.413c0-3.04 1.855-4.696 4.566-4.696 1.297 0 2.41.097 2.734.14v3.17l-1.875.001c-1.472 0-1.757.7-1.757 1.725v2.267h3.516l-.458 3.62h-3.058V24h6.005C23.407 24 24 23.407 24 22.676V1.325C24 .593 23.407 0 22.675 0z"/>
            </svg>
          </a>
          <a href="https://www.instagram.com/dubrovnikcoast" target="_blank" rel="noopener noreferrer" class="hover:text-white">
            <!-- Instagram SVG -->
            <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24">
              <path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.206.058 2.003.248 2.473.415a4.918 4.918 0 011.688 1.093 4.918 4.918 0 011.093 1.688c.167.47.357 1.267.415 2.473.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.058 1.206-.248 2.003-.415 2.473a4.92 4.92 0 01-1.093 1.688 4.92 4.92 0 01-1.688 1.093c-.47.167-1.267.357-2.473.415-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.206-.058-2.003-.248-2.473-.415a4.918 4.918 0 01-1.688-1.093 4.918 4.918 0 01-1.093-1.688c-.167-.47-.357-1.267-.415-2.473C2.175 15.747 2.163 15.367 2.163 12s.012-3.584.07-4.85c.058-1.206.248-2.003.415-2.473a4.918 4.918 0 011.093-1.688A4.918 4.918 0 015.359 2.578c.47-.167 1.267-.357 2.473-.415C8.416 2.175 8.796 2.163 12 2.163zm0-2.163C8.741 0 8.332.012 7.052.07 5.778.127 4.668.316 3.707.706a7.075 7.075 0 00-2.579 1.637A7.075 7.075 0 00.706 4.706C.316 5.667.127 6.777.07 8.051.012 9.332 0 9.741 0 12s.012 2.668.07 3.948c.057 1.274.246 2.384.636 3.345a7.075 7.075 0 001.637 2.579 7.075 7.075 0 002.579 1.637c.961.39 2.071.579 3.345.636 1.28.058 1.689.07 3.948.07s2.668-.012 3.948-.07c1.274-.057 2.384-.246 3.345-.636a7.075 7.075 0 002.579-1.637 7.075 7.075 0 001.637-2.579c.39-.961.579-2.071.636-3.345.058-1.28.07-1.689.07-3.948s-.012-2.668-.07-3.948c-.057-1.274-.246-2.384-.636-3.345a7.075 7.075 0 00-1.637-2.579A7.075 7.075 0 0020.293.706C19.332.316 18.222.127 16.948.07 15.668.012 15.259 0 12 0z"/>
              <circle cx="12" cy="12" r="3.2"/>
              <path d="M18.406 5.594a1.44 1.44 0 11-2.876 0 1.44 1.44 0 012.876 0z"/>
            </svg>
          </a>
        </div>
        <p class="text-gray-400 font-medium">Follow us for exclusive offers & inspiration</p>
      </div>

      <!-- Payment Methods / Trust Badges -->
      <div>
        <h3 class="font-serif text-lg text-white mb-4">Secure Payments</h3>
        <div class="flex space-x-4">
          <img src="img/payments/stripefooter.png" alt="Stripe" class="h-8 w-auto" />
        </div>
      </div>
    </div>

    <!-- Subfooter -->
    <div class="bg-gray-900 text-gray-500 py-4">
      <div class="container mx-auto px-6 text-center text-sm">
        © 2025 Dubrovnik Coast. All rights reserved.
      </div>
    </div>
  </footer>




  <!-- ========== EXTERNAL SCRIPT ========== -->
  <script src="main.js"></script>
</body>
</html>


