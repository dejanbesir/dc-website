document.addEventListener('DOMContentLoaded', () => {
  initDynamicCollections();
  initGalleryAltInputs();
  initRobotsDirectiveToggle();
});

function initDynamicCollections() {
  document.querySelectorAll('[data-add-row]').forEach((button) => {
    button.addEventListener('click', () => {
      const target = button.getAttribute('data-add-row');
      if (!target) return;

      const container = document.querySelector(`[data-collection="${target}"]`);
      const template = document.getElementById(`template-${target}`);
      if (!container || !(template instanceof HTMLTemplateElement)) return;

      const clone = template.content.cloneNode(true);
      container.appendChild(clone);
    });
  });

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (!target.matches('[data-remove-row]')) return;

    const row = target.closest('[data-collection-row]');
    if (row) {
      row.remove();
    }
  });
}

function initGalleryAltInputs() {
  const fileInput = document.querySelector('[data-gallery-input]');
  const altContainer = document.querySelector('[data-gallery-alt]');
  if (!fileInput || !altContainer) return;

  fileInput.addEventListener('change', () => {
    altContainer.innerHTML = '';
    const files = fileInput.files;
    if (!files || !files.length) return;

    Array.from(files).forEach((file, index) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'grid md:grid-cols-2 gap-3 bg-white border border-slate-200 rounded px-3 py-3';
      wrapper.dataset.collectionRow = '';

      const title = document.createElement('div');
      title.className = 'text-xs font-semibold text-slate-500 md:col-span-2';
      title.textContent = `Image ${index + 1}: ${file.name}`;
      wrapper.appendChild(title);

      const altLabel = document.createElement('label');
      altLabel.className = 'flex flex-col gap-1 text-sm';

      const altSpan = document.createElement('span');
      altSpan.className = 'font-medium text-slate-700';
      altSpan.textContent = 'Alt Text';
      altLabel.appendChild(altSpan);

      const altInput = document.createElement('input');
      altInput.type = 'text';
      altInput.name = 'gallery_alt[]';
      altInput.className = 'border border-slate-300 rounded px-2 py-1';
      altInput.placeholder = 'Describe the image';
      altInput.required = true;
      altLabel.appendChild(altInput);

      const captionLabel = document.createElement('label');
      captionLabel.className = 'flex flex-col gap-1 text-sm';

      const captionSpan = document.createElement('span');
      captionSpan.className = 'font-medium text-slate-700';
      captionSpan.textContent = 'Caption (optional)';
      captionLabel.appendChild(captionSpan);

      const captionInput = document.createElement('textarea');
      captionInput.name = 'gallery_caption[]';
      captionInput.className = 'border border-slate-300 rounded px-2 py-1';
      captionInput.rows = 2;
      captionLabel.appendChild(captionInput);

      wrapper.appendChild(altLabel);
      wrapper.appendChild(captionLabel);

      altContainer.appendChild(wrapper);
    });
  });
}

function initRobotsDirectiveToggle() {
  const select = document.querySelector('select[name="robots_directives"]');
  const customInput = document.querySelector('[data-robots-custom]');
  if (!select || !customInput) return;

  const sync = () => {
    if (select.value === 'custom') {
      customInput.classList.remove('hidden');
      customInput.required = true;
    } else {
      customInput.classList.add('hidden');
      customInput.required = false;
      if (!customInput.dataset.keepValue) {
        customInput.value = '';
      }
    }
  };

  if (customInput.value) {
    customInput.dataset.keepValue = '1';
  }

  select.addEventListener('change', sync);
  sync();
}
