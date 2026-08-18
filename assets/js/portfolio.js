window.addEventListener('pageshow', (event) => {
  if (event.persisted) {
    window.location.reload();
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const nav = document.getElementById('siteNav');
  const toggle = document.getElementById('navToggle');
  const links = document.getElementById('navLinks');

  const onScroll = () => {
    if (!nav) return;
    nav.classList.toggle('scrolled', window.scrollY > 20);
  };
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  if (toggle && links) {
    toggle.addEventListener('click', () => {
      const open = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    links.querySelectorAll('a').forEach((a) => {
      a.addEventListener('click', () => {
        links.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // Active section highlighting
  const sections = document.querySelectorAll('main section[id]');
  const navAnchors = document.querySelectorAll('.nav-links a[href^="#"]');
  const spy = () => {
    let current = '';
    sections.forEach((sec) => {
      if (window.scrollY >= sec.offsetTop - 120) {
        current = sec.id;
      }
    });
    navAnchors.forEach((a) => {
      a.classList.toggle('active', a.getAttribute('href') === `#${current}`);
    });
  };
  window.addEventListener('scroll', spy, { passive: true });
  spy();

  // Reveal on scroll
  const reveals = document.querySelectorAll('.reveal, .skill-item');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible', 'in-view');
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
    );
    reveals.forEach((el) => io.observe(el));
  } else {
    reveals.forEach((el) => el.classList.add('visible', 'in-view'));
  }

  const certModal = document.getElementById('certModal');
  const certModalImg = document.getElementById('certModalImage');
  const certModalCaption = document.getElementById('certModalCaption');
  const certModalClose = certModal?.querySelector('.cert-modal-close');
  let certLastFocus = null;

  const closeCertModal = () => {
    if (!certModal || !certModal.classList.contains('is-open')) return;
    certModal.classList.remove('is-open');
    certModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('cert-modal-open');
    if (certModalImg) {
      certModalImg.removeAttribute('src');
      certModalImg.alt = '';
    }
    if (certModalCaption) {
      certModalCaption.textContent = '';
    }
    if (certLastFocus && typeof certLastFocus.focus === 'function') {
      certLastFocus.focus();
    }
  };

  const openCertModal = (trigger) => {
    const src = trigger.getAttribute('data-cert-src');
    if (!src || !certModal || !certModalImg) return;
    const title = trigger.getAttribute('data-cert-title') || 'Certificate';
    const org = trigger.getAttribute('data-cert-org') || '';
    certLastFocus = trigger;
    certModalImg.src = src;
    certModalImg.alt = title;
    if (certModalCaption) {
      certModalCaption.textContent = org ? `${title} — ${org}` : title;
    }
    certModal.classList.add('is-open');
    certModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('cert-modal-open');
    certModalClose?.focus();
  };

  document.querySelectorAll('[data-cert-src]').forEach((btn) => {
    btn.addEventListener('click', () => openCertModal(btn));
  });

  certModal?.querySelectorAll('[data-cert-close]').forEach((el) => {
    el.addEventListener('click', closeCertModal);
  });

  document.addEventListener('keydown', (e) => {
    if (!certModal?.classList.contains('is-open')) return;
    if (e.key === 'Escape') {
      e.preventDefault();
      closeCertModal();
      return;
    }
    if (e.key === 'Tab') {
      e.preventDefault();
      certModalClose?.focus();
    }
  });
});
