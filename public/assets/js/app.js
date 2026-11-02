/* Shared page behaviour: menu, messages, confirmations and client-side validation. */
(function () {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

  // Mobile menu
  const toggle = $('.nav-toggle');
  if (toggle) {
    toggle.addEventListener('click', () => {
      const open = document.body.classList.toggle('nav-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Flash messages: close button and auto-hide for success
  $$('.flash').forEach((f) => {
    $('.flash-close', f)?.addEventListener('click', () => f.remove());
    if (f.classList.contains('flash-success')) setTimeout(() => f.classList.add('fade'), 5000);
  });

  // "Are you sure?" for delete / cancel forms
  $$('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      if (!confirm(form.dataset.confirm)) e.preventDefault();
    });
  });

  // Auto-submit selects (e.g. change status / role)
  $$('select[data-autosubmit]').forEach((s) => s.addEventListener('change', () => s.form.submit()));

  // Show / hide password
  $$('.toggle-password').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = btn.parentElement.querySelector('input');
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.dataset.tip = show ? 'Hide password' : 'Show password';
      btn.setAttribute('aria-label', btn.dataset.tip);
    });
  });

  // Date tabs on the movie page
  $$('.date-tab').forEach((tab) => {
    tab.addEventListener('click', () => {
      $$('.date-tab').forEach((t) => { t.classList.toggle('active', t === tab); t.setAttribute('aria-selected', t === tab); });
      $$('.showtime-list').forEach((l) => l.classList.toggle('hidden', l.dataset.date !== tab.dataset.date));
    });
  });

  // Password strength checklist
  const rules = {
    len: (v) => v.length >= 8,
    upper: (v) => /[A-Z]/.test(v),
    lower: (v) => /[a-z]/.test(v),
    num: (v) => /[0-9]/.test(v),
    sym: (v) => /[^A-Za-z0-9]/.test(v),
  };
  const isStrong = (v) => Object.values(rules).every((r) => r(v));
  $$('input[data-strong]').forEach((input) => {
    const list = input.closest('.field')?.querySelector('.pw-rules');
    if (!list) return;
    input.addEventListener('input', () => {
      $$('li', list).forEach((li) => li.classList.toggle('ok', rules[li.dataset.rule](input.value)));
    });
  });

  // Card number / expiry formatting on the payment page
  $$('input[data-card]').forEach((i) => i.addEventListener('input', () => {
    i.value = i.value.replace(/\D/g, '').slice(0, 19).replace(/(\d{4})(?=\d)/g, '$1 ');
  }));
  $$('input[data-expiry]').forEach((i) => i.addEventListener('input', (e) => {
    let v = i.value.replace(/\D/g, '').slice(0, 4);
    if (v.length >= 3) v = v.slice(0, 2) + '/' + v.slice(2);
    i.value = v;
  }));
  const luhn = (num) => {
    const d = num.replace(/\D/g, '');
    if (d.length < 13 || d.length > 19) return false;
    let sum = 0, alt = false;
    for (let k = d.length - 1; k >= 0; k--) {
      let n = +d[k];
      if (alt) { n *= 2; if (n > 9) n -= 9; }
      sum += n; alt = !alt;
    }
    return sum % 10 === 0;
  };

  // Seat-hold countdown on the payment page
  const timer = $('.hold-timer');
  if (timer) {
    let left = +timer.dataset.seconds;
    const out = $('#hold-left');
    const tick = () => {
      if (left <= 0) {
        out.textContent = 'expired';
        timer.classList.add('expired');
        const btn = $('#pay-form button[type=submit]');
        if (btn) { btn.disabled = true; btn.textContent = 'Seat hold expired - choose seats again'; }
        return;
      }
      out.textContent = Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
      if (left < 60) timer.classList.add('urgent');
      left--;
      setTimeout(tick, 1000);
    };
    tick();
  }

  // Client-side validation (the server checks everything again).
  function fieldMessage(input) {
    const v = input.value.trim();
    if (input.disabled || input.type === 'hidden') return '';
    if (input.required && !v && input.type !== 'file') return 'This field is required.';
    if (!v) return '';
    if (input.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'Enter a valid email address.';
    if (input.hasAttribute('data-strong') && !isStrong(input.value))
      return 'Use 8+ characters with an uppercase letter, a lowercase letter, a number and a symbol.';
    if (input.dataset.match) {
      const other = input.form.querySelector('[name="' + input.dataset.match + '"]');
      if (other && other.value !== input.value) return 'The two passwords do not match.';
    }
    if (input.hasAttribute('data-card') && !luhn(v)) return 'Enter a valid card number.';
    if (input.validity && !input.validity.valid) return input.dataset.msg || input.validationMessage;
    return '';
  }

  function showError(input, msg) {
    const field = input.closest('.field') || input.parentElement;
    let el = field.querySelector('.field-error');
    if (msg) {
      if (!el) {
        el = document.createElement('small');
        el.className = 'field-error';
        field.appendChild(el);
      }
      el.textContent = msg;
      field.classList.add('invalid');
    } else {
      el?.remove();
      field.classList.remove('invalid');
    }
  }

  $$('form.js-validate').forEach((form) => {
    const inputs = $$('input, select, textarea', form).filter((i) => i.name && i.type !== 'hidden');
    inputs.forEach((i) => {
      i.addEventListener('blur', () => showError(i, fieldMessage(i)));
      i.addEventListener('input', () => {
        if ((i.closest('.field') || i.parentElement).classList.contains('invalid')) showError(i, fieldMessage(i));
      });
    });
    form.addEventListener('submit', (e) => {
      let first = null;
      inputs.forEach((i) => {
        const msg = fieldMessage(i);
        showError(i, msg);
        if (msg && !first) first = i;
      });
      if (first) {
        e.preventDefault();
        first.focus();
      }
    });
  });

  // Admin: fill the end time from the movie length when adding a screening
  const movieSel = $('#st-movie'), startIn = $('#st-start'), endIn = $('#st-end');
  if (movieSel && startIn && endIn) {
    const fill = () => {
      const dur = +(movieSel.selectedOptions[0]?.dataset.duration || 0);
      if (!dur || !startIn.value) return;
      const [h, m] = startIn.value.split(':').map(Number);
      let t = h * 60 + m + dur + 15; // 15 minutes for cleaning between shows
      t = Math.min(t, 23 * 60 + 59);
      endIn.value = String(Math.floor(t / 60)).padStart(2, '0') + ':' + String(t % 60).padStart(2, '0');
    };
    movieSel.addEventListener('change', fill);
    startIn.addEventListener('change', fill);
  }

  // Admin: hall seat-grid preview of total capacity
  const rowsIn = $('#hall-rows'), perIn = $('#hall-per'), capOut = $('#hall-cap');
  if (rowsIn && perIn && capOut) {
    const upd = () => (capOut.textContent = (+rowsIn.value || 0) * (+perIn.value || 0));
    rowsIn.addEventListener('input', upd);
    perIn.addEventListener('input', upd);
    upd();
  }
})();
