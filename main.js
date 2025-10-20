// main.js

// 1. Mobile menu toggle
const mobileMenuButton = document.getElementById('mobile-menu-button');
const mobileMenu = document.getElementById('mobile-menu');

mobileMenuButton.addEventListener('click', () => {
  mobileMenu.classList.toggle('hidden');
});

// ===== Featured Carousel =====
document.addEventListener('DOMContentLoaded', () => {
  // --- Mobile menu toggle (your existing code) ---
  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileMenu       = document.getElementById('mobile-menu');
  mobileMenuButton.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
  });

  // --- Carousel logic ---
  const carouselEl = document.getElementById('carousel');
  const slides     = Array.from(carouselEl.querySelectorAll('.carousel-item'));
  const prevBtn    = document.getElementById('prevBtn');
  const nextBtn    = document.getElementById('nextBtn');
  let currentIndex = 0;

  function showSlide(idx) {
    slides.forEach((slide, i) => {
      slide.classList.toggle('hidden', i !== idx);
    });
  }

  // Button handlers
  prevBtn.addEventListener('click', () => {
    currentIndex = (currentIndex - 1 + slides.length) % slides.length;
    showSlide(currentIndex);
  });
  nextBtn.addEventListener('click', () => {
    currentIndex = (currentIndex + 1) % slides.length;
    showSlide(currentIndex);
  });

  // Auto-rotate
  setInterval(() => {
    currentIndex = (currentIndex + 1) % slides.length;
    showSlide(currentIndex);
  }, 6000);

  // Kick it off
  showSlide(currentIndex);
});

document.addEventListener('DOMContentLoaded', () => {
  const allBtn    = document.getElementById('tab-all');
  const villasBtn = document.getElementById('tab-villas');
  const aptsBtn   = document.getElementById('tab-apts');
  const cards     = document.querySelectorAll('.property-card');

  function activate(btn) {
    [allBtn, villasBtn, aptsBtn].forEach(b => {
      b.classList.toggle('bg-indigo-600', b === btn);
      b.classList.toggle('text-white',     b === btn);
      b.classList.toggle('bg-gray-200',    b !== btn);
      b.classList.toggle('text-gray-700',  b !== btn);
    });
  }

  function filter(type) {
    cards.forEach(card => {
      // show all, or only villas/apartments
      card.classList.toggle('hidden',
        type !== 'all' && card.dataset.type !== type
      );
    });
  }

  allBtn.addEventListener('click', () => {
    activate(allBtn);
    filter('all');
  });

  villasBtn.addEventListener('click', () => {
    activate(villasBtn);
    filter('villa');
  });

  aptsBtn.addEventListener('click', () => {
    activate(aptsBtn);
    filter('apartment');
  });

  // initialize
  allBtn.click();
});


// ===== Inline Gallery (delegated, robust) =====
(function(){
  function initInlineGallery() {
    const root = document.querySelector('[data-gallery-inline]');
    if (!root) {
      console.warn('[Gallery] data-gallery-inline root not found');
      return;
    }
    const mainImg = root.querySelector('#vaMainImage');
    const captionEl = root.querySelector('#vaCaption');
    if (!mainImg) {
      console.warn('[Gallery] #vaMainImage not found inside gallery root');
      return;
    }

    // Build a live list of thumbs whenever needed
    const getThumbs = () => Array.from(root.querySelectorAll('[data-gallery-thumb]'));

    // Helper to set active state
    function setActive(thumbBtn) {
      const thumbs = getThumbs();
      thumbs.forEach(t => {
        const active = t === thumbBtn;
        t.setAttribute('aria-selected', String(active));
        t.classList.toggle('ring-2', active);
        t.classList.toggle('ring-primary/80', active);
      });
    }

    // Swap main image from a given thumb element
    function showFromThumb(el) {
      const full = el.getAttribute('data-full') || el.querySelector('img')?.src;
      const alt = el.getAttribute('data-alt') || el.querySelector('img')?.alt || 'Property image';
      if (!full) return;

      mainImg.src = full;
      mainImg.alt = alt;
      if (captionEl) captionEl.textContent = alt;

      setActive(el);
      // Ke
