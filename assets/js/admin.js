document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const toggle = document.getElementById('sidebarToggle');

  const setOpen = (open) => {
    sidebar?.classList.toggle('open', open);
    overlay?.classList.toggle('show', open);
    document.body.classList.toggle('menu-open', open);
    if (toggle) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    }
    if (sidebar) sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
  };

  const closeSidebar = () => setOpen(false);

  toggle?.addEventListener('click', () => {
    const isOpen = sidebar?.classList.contains('open');
    setOpen(!isOpen);
  });

  overlay?.addEventListener('click', closeSidebar);

  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeSidebar();
  });

  sidebar?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => closeSidebar());
  });

  // Keep menu usable if viewport changes while open
  window.addEventListener('resize', () => {
    if (sidebar?.classList.contains('open')) {
      // no-op: off-canvas works at all sizes; ensure overlay stays aligned
      overlay?.classList.add('show');
    }
  });

  // Auto data-labels for responsive table cards
  document.querySelectorAll('table.data').forEach((table) => {
    const headers = [...table.querySelectorAll('thead th')].map((th) =>
      (th.textContent || '').trim()
    );
    table.querySelectorAll('tbody tr').forEach((row) => {
      if (row.querySelector('.empty')) return;
      row.querySelectorAll('td').forEach((td, index) => {
        if (!td.hasAttribute('data-label') && headers[index]) {
          td.setAttribute('data-label', headers[index] || 'Details');
        }
      });
    });
  });

  document.querySelectorAll('[data-table-filter]').forEach((input) => {
    const target = document.querySelector(input.getAttribute('data-table-filter'));
    if (!target) return;
    input.addEventListener('input', () => {
      const q = input.value.trim().toLowerCase();
      target.querySelectorAll('tbody tr').forEach((row) => {
        if (row.querySelector('.empty')) return;
        const text = row.textContent?.toLowerCase() || '';
        row.style.display = !q || text.includes(q) ? '' : 'none';
      });
    });
  });

  document.querySelectorAll('[data-list-filter]').forEach((input) => {
    const selector = input.getAttribute('data-list-filter');
    if (!selector) return;
    input.addEventListener('input', () => {
      const q = input.value.trim().toLowerCase();
      document.querySelectorAll(selector).forEach((item) => {
        const text = (item.getAttribute('data-filter-text') || item.textContent || '').toLowerCase();
        item.style.display = !q || text.includes(q) ? '' : 'none';
      });
    });
  });

  const hideDialog = document.getElementById('hidePortfolioDialog');
  const visibilityForm = document.getElementById('visibilityForm');
  const hideTrigger = document.querySelector('[data-visibility-hide]');
  const hideCancel = document.querySelector('[data-visibility-cancel]');
  const hideConfirm = document.querySelector('[data-visibility-confirm]');

  hideTrigger?.addEventListener('click', () => {
    if (hideDialog && typeof hideDialog.showModal === 'function') {
      hideDialog.showModal();
      hideCancel?.focus();
      return;
    }
    if (window.confirm('Your portfolio will no longer be visible to public visitors. You can make it visible again anytime from the Admin panel.')) {
      visibilityForm?.submit();
    }
  });

  hideCancel?.addEventListener('click', () => {
    hideDialog?.close();
  });

  hideConfirm?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    visibilityForm?.submit();
  });

  hideDialog?.addEventListener('click', (event) => {
    if (event.target === hideDialog) {
      hideDialog.close();
    }
  });

  document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
    const input = btn.closest('.password-field')?.querySelector('input');
    if (!input) return;

    btn.addEventListener('click', () => {
      const reveal = input.type === 'password';
      input.type = reveal ? 'text' : 'password';
      btn.setAttribute('aria-pressed', reveal ? 'true' : 'false');
      btn.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
      const icon = btn.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-eye', !reveal);
        icon.classList.toggle('fa-eye-slash', reveal);
      }
    });
  });
});
