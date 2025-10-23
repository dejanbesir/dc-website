class BookingWidget {
  constructor(root) {
    this.root = root;
    this.propertyId = Number(root.dataset.propertyId);
    this.propertyName = root.dataset.propertyName;
    this.calendarEl = root.querySelector('[data-booking-calendar]');
    this.confirmBtn = root.querySelector('[data-booking-confirm-dates]');
    this.resetBtn = root.querySelector('[data-booking-reset]');
    this.statusEl = root.querySelector('[data-booking-status]');
    this.detailsSection = root.querySelector('[data-booking-details]');
    this.form = root.querySelector('[data-booking-form]');
    this.formStatusEl = root.querySelector('[data-booking-form-status]');
    this.arrivalDisplay = root.querySelector('[data-booking-arrival-display]');
    this.departureDisplay = root.querySelector('[data-booking-departure-display]');
    this.nextStepsSection = root.querySelector('[data-booking-next-steps]');
    this.emailDisplay = root.querySelector('[data-booking-email]');
    this.continueLink = root.querySelector('[data-booking-continue]');

    this.arrivalDate = null;
    this.departureDate = null;
    this.availability = [];
    this.monthsToRender = 4;

    this.bindEvents();
    this.loadAvailability();
  }

  bindEvents() {
    this.confirmBtn?.addEventListener('click', () => this.showDetailsForm());
    this.resetBtn?.addEventListener('click', () => this.resetSelection());
    this.form?.addEventListener('submit', (event) => this.handleSubmit(event));

    const backBtn = this.root.querySelector('[data-booking-back]');
    backBtn?.addEventListener('click', () => {
      this.detailsSection?.classList.add('hidden');
      this.root.querySelector('[data-booking-next-steps]')?.classList.add('hidden');
      this.root.querySelector('[data-booking-confirm-dates]')?.focus();
    });
  }

  async loadAvailability() {
    if (!this.calendarEl) return;
    try {
      const start = new Date();
      const end = new Date();
      end.setMonth(end.getMonth() + this.monthsToRender);

      const params = new URLSearchParams({
        property: String(this.propertyId),
        start: start.toISOString().slice(0, 10),
        end: end.toISOString().slice(0, 10),
      });
      const response = await fetch(`/api/booking/availability.php?${params.toString()}`, {
        headers: { 'Accept': 'application/json' },
      });
      if (!response.ok) throw new Error('Failed to load availability');
      const payload = await response.json();
      if (!payload.success) throw new Error(payload.error || 'Unable to load availability');
      this.availability = payload.data || [];
      this.renderCalendars();
    } catch (error) {
      this.setStatus(error.message || 'Unable to load availability', true);
    }
  }

  renderCalendars() {
    if (!this.calendarEl) return;
    this.calendarEl.innerHTML = '';
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    for (let monthOffset = 0; monthOffset < this.monthsToRender; monthOffset += 1) {
      const date = new Date(today.getFullYear(), today.getMonth() + monthOffset, 1);
      const monthEl = document.createElement('div');
      monthEl.className = 'border border-gray-200 rounded-lg overflow-hidden';
      monthEl.innerHTML = `
        <div class="bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 text-center">
          ${date.toLocaleString('default', { month: 'long', year: 'numeric' })}
        </div>
        <div class="grid grid-cols-7 text-xs text-center text-gray-500 border-t border-gray-200">
          ${['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].map(day => `<div class="py-1 bg-gray-50">${day}</div>`).join('')}
        </div>
        <div class="grid grid-cols-7 text-sm" data-calendar-days></div>
      `;
      const daysEl = monthEl.querySelector('[data-calendar-days]');

      const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
      const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);

      const padStart = (firstDay.getDay() + 6) % 7; // convert to Monday=0
      for (let i = 0; i < padStart; i += 1) {
        const blank = document.createElement('div');
        blank.className = 'py-2';
        daysEl.appendChild(blank);
      }

      for (let day = 1; day <= lastDay.getDate(); day += 1) {
        const current = new Date(date.getFullYear(), date.getMonth(), day);
        const iso = current.toISOString().slice(0, 10);
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'py-2 text-gray-700 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500';
        button.textContent = String(day);
        button.dataset.date = iso;

        if (current < today) {
          button.disabled = true;
          button.classList.add('text-gray-400', 'cursor-not-allowed');
        } else if (this.isBlocked(iso)) {
          button.disabled = true;
          button.classList.add('bg-gray-200', 'text-gray-500', 'cursor-not-allowed');
        } else {
          button.addEventListener('click', () => this.handleDateClick(iso, button));
        }

        daysEl.appendChild(button);
      }

      this.calendarEl.appendChild(monthEl);
    }
  }

  isBlocked(dateStr) {
    return this.availability.some((block) => {
      const start = block.start;
      const end = block.end;
      return dateStr >= start && dateStr < end;
    });
  }

  handleDateClick(iso, button) {
    if (this.arrivalDate && this.departureDate) {
      this.arrivalDate = iso;
      this.departureDate = null;
    } else if (!this.arrivalDate) {
      this.arrivalDate = iso;
    } else if (iso <= this.arrivalDate) {
      this.arrivalDate = iso;
    } else {
      this.departureDate = iso;
    }

    this.highlightSelection();
    this.updateDisplays();
  }

  highlightSelection() {
    const buttons = this.root.querySelectorAll('[data-date]');
    buttons.forEach((btn) => {
      btn.classList.remove('bg-indigo-600', 'text-white', 'bg-indigo-100');
      const date = btn.dataset.date;
      if (!date || btn.disabled) return;
      if (this.arrivalDate && date === this.arrivalDate) {
        btn.classList.add('bg-indigo-600', 'text-white');
      } else if (this.departureDate && date === this.departureDate) {
        btn.classList.add('bg-indigo-600', 'text-white');
      } else if (this.arrivalDate && this.departureDate && date > this.arrivalDate && date < this.departureDate) {
        btn.classList.add('bg-indigo-100');
      }
    });

    const ready = Boolean(this.arrivalDate && this.departureDate);
    this.confirmBtn.disabled = !ready;
    this.resetBtn.classList.toggle('hidden', !(this.arrivalDate || this.departureDate));

    if (!ready) {
      this.setStatus('Select your arrival date, then departure date.', false);
    } else {
      this.setStatus('', false);
    }
  }

  updateDisplays() {
    if (this.arrivalDisplay) {
      this.arrivalDisplay.textContent = this.arrivalDate || '—';
    }
    if (this.departureDisplay) {
      this.departureDisplay.textContent = this.departureDate || '—';
    }
  }

  setStatus(message, isError = false) {
    if (!this.statusEl) return;
    this.statusEl.textContent = message;
    this.statusEl.classList.toggle('text-rose-600', isError);
    this.statusEl.classList.toggle('text-gray-500', !isError);
  }

  showDetailsForm() {
    if (!this.arrivalDate || !this.departureDate) return;
    this.detailsSection?.classList.remove('hidden');
    this.detailsSection?.scrollIntoView({ behavior: 'smooth' });
  }

  resetSelection() {
    this.arrivalDate = null;
    this.departureDate = null;
    this.highlightSelection();
    this.updateDisplays();
    this.detailsSection?.classList.add('hidden');
    this.nextStepsSection?.classList.add('hidden');
    this.formStatusEl.textContent = '';
  }

  async handleSubmit(event) {
    event.preventDefault();
    if (!this.form || !this.arrivalDate || !this.departureDate) return;
    const formData = new FormData(this.form);
    const agesRaw = String(formData.get('traveller_ages') || '');
    const parsedTravellers = agesRaw.split(',').map((age) => Number.parseInt(age.trim(), 10)).filter((val) => !Number.isNaN(val) && val > 0);

    const travellers = parsedTravellers.map((age) => {
      if (age <= 2) return { type: 'infant', age };
      if (age < 18) return { type: 'child', age };
      return { type: 'adult', age };
    });

    const payload = {
      property_id: this.propertyId,
      arrival_date: this.arrivalDate,
      departure_date: this.departureDate,
      full_name: formData.get('full_name'),
      email: formData.get('email'),
      phone: formData.get('phone'),
      country: formData.get('country'),
      address: formData.get('address'),
      city: formData.get('city'),
      region: formData.get('region'),
      postal_code: formData.get('postal_code'),
      adults: Number(formData.get('adults') || 1),
      children: Number(formData.get('children') || 0),
      infants: Number(formData.get('infants') || 0),
      travellers,
    };

    try {
      this.formStatusEl.textContent = 'Creating your reservation...';
      this.formStatusEl.classList.remove('text-rose-600');

      const response = await fetch('/api/booking/start.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.error || 'Unable to create booking.');
      }

      this.formStatusEl.textContent = 'Email sent. Please verify to continue.';
      this.detailsSection?.classList.add('hidden');
      if (this.nextStepsSection) {
        this.nextStepsSection.classList.remove('hidden');
      }
      if (this.emailDisplay) {
        this.emailDisplay.textContent = payload.email;
      }
      if (this.continueLink) {
        this.continueLink.href = `/booking/checkout.php?reference=${encodeURIComponent(data.reference)}`;
        this.continueLink.hidden = false;
      }
    } catch (error) {
      this.formStatusEl.textContent = error.message || 'Something went wrong.';
      this.formStatusEl.classList.add('text-rose-600');
    }
  }
}

function initBookingWidgets() {
  document.querySelectorAll('[data-booking-widget]').forEach((widget) => {
    if (widget.dataset.initialised) return;
    widget.dataset.initialised = 'true';
    try {
      new BookingWidget(widget);
    } catch (error) {
      console.error('Failed to initialise booking widget', error);
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initBookingWidgets();
});
