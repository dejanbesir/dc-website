'use strict';

// Core behaviour initialisers executed once the DOM is ready.
document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initFeaturedCarousel();
  initPropertyFilters();
  initInlineGalleries();
});

function initMobileMenu() {
  const toggle = document.getElementById('mobile-menu-button');
  const menu = document.getElementById('mobile-menu');
  if (!toggle || !menu) return;

  toggle.addEventListener('click', () => {
    menu.classList.toggle('hidden');
  });
}

function initFeaturedCarousel() {
  const carouselEl = document.getElementById('carousel');
  if (!carouselEl) return;

  const slides = Array.from(carouselEl.querySelectorAll('.carousel-item'));
  if (slides.length === 0) return;

  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  let currentIndex = 0;

  const showSlide = (idx) => {
    if (slides.length === 0) return;
    const total = slides.length;
    currentIndex = ((idx % total) + total) % total;

    slides.forEach((slide, i) => {
      const isActive = i === currentIndex;
      slide.classList.toggle('hidden', !isActive);
      slide.setAttribute('aria-hidden', String(!isActive));
    });
  };

  prevBtn?.addEventListener('click', () => {
    showSlide(currentIndex - 1);
  });

  nextBtn?.addEventListener('click', () => {
    showSlide(currentIndex + 1);
  });

  if (slides.length > 1) {
    setInterval(() => {
      showSlide(currentIndex + 1);
    }, 6000);
  }

  showSlide(currentIndex);
}

function initPropertyFilters() {
  const allBtn = document.getElementById('tab-all');
  const villasBtn = document.getElementById('tab-villas');
  const aptsBtn = document.getElementById('tab-apts');
  const cards = document.querySelectorAll('.property-card');

  if (!allBtn || !villasBtn || !aptsBtn || cards.length === 0) return;

  const activate = (target) => {
    [allBtn, villasBtn, aptsBtn].forEach((btn) => {
      const isActive = btn === target;
      btn.classList.toggle('bg-indigo-600', isActive);
      btn.classList.toggle('text-white', isActive);
      btn.classList.toggle('bg-gray-200', !isActive);
      btn.classList.toggle('text-gray-700', !isActive);
    });
  };

  const filterCards = (type) => {
    cards.forEach((card) => {
      const shouldHide = type !== 'all' && card.dataset.type !== type;
      card.classList.toggle('hidden', shouldHide);
    });
  };

  allBtn.addEventListener('click', () => {
    activate(allBtn);
    filterCards('all');
  });

  villasBtn.addEventListener('click', () => {
    activate(villasBtn);
    filterCards('villa');
  });

  aptsBtn.addEventListener('click', () => {
    activate(aptsBtn);
    filterCards('apartment');
  });

  // Initialise with "All" active.
  activate(allBtn);
  filterCards('all');
}

function initInlineGalleries() {
  const galleries = document.querySelectorAll('[data-gallery-inline]');
  if (!galleries.length) return;

  galleries.forEach((root) => {
    const mainImg = root.querySelector('[data-gallery-main]');
    if (!(mainImg instanceof HTMLImageElement)) return;

    const captionEl = root.querySelector('[data-gallery-caption]');
    const thumbButtons = Array.from(root.querySelectorAll('[data-gallery-thumb]'));
    if (!thumbButtons.length) return;

    const defaultAlt = mainImg.getAttribute('alt') || '';
    let currentIndex = thumbButtons.findIndex((btn) => btn.getAttribute('aria-selected') === 'true');
    if (currentIndex < 0) currentIndex = 0;

    const update = (index, { focusThumb = false } = {}) => {
      if (!thumbButtons.length) return;
      const total = thumbButtons.length;
      const normalized = ((index % total) + total) % total;
      currentIndex = normalized;

      thumbButtons.forEach((btn, idx) => {
        const isActive = idx === normalized;
        btn.setAttribute('aria-selected', String(isActive));
        btn.classList.toggle('ring-2', isActive);
        btn.classList.toggle('ring-primary/80', isActive);
        btn.classList.toggle('ring-offset-2', isActive);
      });

      const activeBtn = thumbButtons[normalized];
      const thumbImg = activeBtn.querySelector('img');
      const fullSrc = activeBtn.getAttribute('data-full') || thumbImg?.currentSrc || thumbImg?.src;
      const altText = activeBtn.getAttribute('data-alt') || thumbImg?.alt || defaultAlt;
      const captionText = activeBtn.getAttribute('data-caption') || altText;

      if (fullSrc) {
        mainImg.src = fullSrc;
      }
      mainImg.alt = altText;

      if (captionEl) {
        captionEl.textContent = captionText;
      }

      if (focusThumb) {
        activeBtn.focus({ preventScroll: true });
      }
    };

    thumbButtons.forEach((btn, index) => {
      btn.addEventListener('click', () => update(index));
      btn.addEventListener('keydown', (event) => {
        switch (event.key) {
          case 'ArrowLeft':
          case 'ArrowUp':
            event.preventDefault();
            update(currentIndex - 1, { focusThumb: true });
            break;
          case 'ArrowRight':
          case 'ArrowDown':
            event.preventDefault();
            update(currentIndex + 1, { focusThumb: true });
            break;
          case 'Home':
            event.preventDefault();
            update(0, { focusThumb: true });
            break;
          case 'End':
            event.preventDefault();
            update(thumbButtons.length - 1, { focusThumb: true });
            break;
          default:
            break;
        }
      });
    });

    const prevBtn = root.querySelector('[data-inline-prev]');
    const nextBtn = root.querySelector('[data-inline-next]');
    prevBtn?.addEventListener('click', () => update(currentIndex - 1));
    nextBtn?.addEventListener('click', () => update(currentIndex + 1));

    update(currentIndex);
  });
}
