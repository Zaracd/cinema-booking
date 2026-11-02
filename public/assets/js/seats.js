/*
 * Seat selection (FR06).
 * Loads the seat map with the Fetch API, lets the user pick seats, updates the
 * total price without reloading, and refreshes every 15 seconds so seats booked
 * by other people show up as taken.
 */
(function () {
  const mapEl = document.getElementById('seat-map');
  if (!mapEl) return;

  const MAX_SEATS = 10;
  const selected = new Map(); // seat_id -> seat
  const listEl = document.getElementById('selected-list');
  const countEl = document.getElementById('ticket-count');
  const totalEl = document.getElementById('total-price');
  const idsInput = document.getElementById('seat-ids');
  const bookBtn = document.getElementById('book-btn');
  const statusEl = document.getElementById('seat-status');
  let currency = '';

  async function load() {
    try {
      const res = await fetch(mapEl.dataset.url, { headers: { Accept: 'application/json' } });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      currency = data.currency;
      if (data.started) {
        statusEl.textContent = 'This screening has started. Booking is closed.';
        bookBtn.disabled = true;
      }
      render(data.seats);
    } catch (err) {
      mapEl.innerHTML = '<p class="muted center">Could not load the seat map. <button type="button" class="link-btn" id="retry">Try again</button></p>';
      document.getElementById('retry').onclick = load;
    }
  }

  function render(seats) {
    // Seats someone else booked since the last refresh are removed from the selection.
    let lost = [];
    seats.forEach((s) => {
      if (s.booked && selected.has(s.seat_id)) {
        lost.push(s.row_label + s.seat_number);
        selected.delete(s.seat_id);
      }
    });

    const rows = {};
    seats.forEach((s) => (rows[s.row_label] = rows[s.row_label] || []).push(s));

    const frag = document.createDocumentFragment();
    Object.keys(rows).forEach((label) => {
      const rowEl = document.createElement('div');
      rowEl.className = 'seat-row';
      rowEl.innerHTML = '<span class="row-label">' + label + '</span>';
      const seatsWrap = document.createElement('div');
      seatsWrap.className = 'row-seats';
      rows[label].forEach((s) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'seat' + (s.seat_type === 'VIP' ? ' vip' : '') + (s.seat_type === 'Accessible' ? ' accessible' : '');
        b.textContent = s.seat_number;
        b.dataset.id = s.seat_id;
        const name = s.row_label + s.seat_number;
        if (s.booked) {
          b.classList.add('booked');
          b.disabled = true;
          b.setAttribute('aria-label', 'Seat ' + name + ', booked');
          b.dataset.tip = name + ' · booked';
        } else {
          b.setAttribute('aria-pressed', selected.has(s.seat_id) ? 'true' : 'false');
          b.setAttribute('aria-label', 'Seat ' + name + ', ' + s.seat_type + ', ' + s.price.toFixed(2) + ' ' + currency);
          b.dataset.tip = name + ' · ' + s.seat_type + ' · ' + s.price.toFixed(2) + ' ' + currency;
          if (selected.has(s.seat_id)) b.classList.add('selected');
          b.addEventListener('click', () => toggle(s, b));
        }
        seatsWrap.appendChild(b);
      });
      rowEl.appendChild(seatsWrap);
      rowEl.insertAdjacentHTML('beforeend', '<span class="row-label">' + label + '</span>');
      frag.appendChild(rowEl);
    });
    mapEl.replaceChildren(frag);
    updateSummary();

    if (lost.length) {
      statusEl.textContent = 'Seat ' + lost.join(', ') + ' was just booked by someone else and removed from your selection.';
      statusEl.classList.add('warn');
    }
  }

  function toggle(seat, btn) {
    if (selected.has(seat.seat_id)) {
      selected.delete(seat.seat_id);
      btn.classList.remove('selected');
      btn.setAttribute('aria-pressed', 'false');
    } else {
      if (selected.size >= MAX_SEATS) {
        statusEl.textContent = 'You can select up to ' + MAX_SEATS + ' seats.';
        statusEl.classList.add('warn');
        return;
      }
      selected.set(seat.seat_id, seat);
      btn.classList.add('selected');
      btn.setAttribute('aria-pressed', 'true');
    }
    statusEl.classList.remove('warn');
    updateSummary();
  }

  function updateSummary() {
    const seats = [...selected.values()].sort((a, b) =>
      a.row_label === b.row_label ? a.seat_number - b.seat_number : a.row_label.localeCompare(b.row_label));
    const total = seats.reduce((sum, s) => sum + s.price, 0);
    listEl.textContent = seats.length ? seats.map((s) => s.row_label + s.seat_number).join(', ') : 'None selected';
    countEl.textContent = seats.length;
    totalEl.textContent = total.toFixed(2) + ' ' + currency;
    idsInput.value = seats.map((s) => s.seat_id).join(',');
    bookBtn.disabled = seats.length === 0;
  }

  document.getElementById('booking-form').addEventListener('submit', (e) => {
    if (!selected.size) {
      e.preventDefault();
      statusEl.textContent = 'Please choose at least one seat.';
      statusEl.classList.add('warn');
    } else {
      bookBtn.disabled = true;
      bookBtn.textContent = 'Reserving your seats...';
    }
  });

  load();
  setInterval(() => { if (!document.hidden) load(); }, 15000);
})();
