// Sekolah Grading — small UI helpers (no framework).
(function () {
  // Mobile sidebar toggle
  const sb = document.getElementById('sidebar');
  const bd = document.getElementById('sbBackdrop');
  const btn = document.getElementById('btnMenu');
  if (btn && sb && bd) {
    const open = (yes) => { sb.classList.toggle('is-open', yes); bd.classList.toggle('is-open', yes); };
    btn.addEventListener('click', () => open(!sb.classList.contains('is-open')));
    bd.addEventListener('click', () => open(false));
  }

  // Keep sidebar scroll position when navigating through menu links.
  // On fresh login, reset scroll position to top.
  if (sb) {
    const SIDEBAR_SCROLL_KEY = 'sg_sidebar_scroll_top';
    const FRESH_LOGIN_KEY = 'sg_fresh_login';
    
    const restoreSidebarScroll = () => {
      // Check if this is a fresh login
      const isFreshLogin = sessionStorage.getItem(FRESH_LOGIN_KEY);
      if (isFreshLogin) {
        // Fresh login: reset scroll to top and remove the flag
        sessionStorage.removeItem(FRESH_LOGIN_KEY);
        sessionStorage.removeItem(SIDEBAR_SCROLL_KEY);
        sb.scrollTop = 0;
      } else {
        // Not fresh login: restore previous scroll position
        const stored = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
        if (stored !== null) {
          const value = Number(stored);
          if (!Number.isNaN(value)) {
            sb.scrollTop = value;
          }
        }
      }
    };

    const saveSidebarScroll = () => {
      sessionStorage.setItem(SIDEBAR_SCROLL_KEY, String(sb.scrollTop));
    };

    restoreSidebarScroll();
    sb.addEventListener('scroll', saveSidebarScroll);
    window.addEventListener('beforeunload', saveSidebarScroll);
  }

  // Collapsible nav sub-menus (e.g. "Verifikasi" > Nilai / Deskripsi Umum).
  // Remembers open/closed state per group for the session so it doesn't
  // collapse again after navigating to one of its own child pages.
  (function () {
    const STORAGE_PREFIX = 'sg_nav_open_';
    document.querySelectorAll('[data-nav-collapsible]').forEach((wrap) => {
      const key = wrap.getAttribute('data-nav-collapsible');
      const btn = wrap.querySelector('[data-nav-toggle]');
      if (!btn || !key) return;

      const stored = sessionStorage.getItem(STORAGE_PREFIX + key);
      if (stored !== null) {
        const isOpen = stored === '1';
        wrap.classList.toggle('is-open', isOpen);
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      }

      btn.addEventListener('click', () => {
        const nowOpen = !wrap.classList.contains('is-open');
        wrap.classList.toggle('is-open', nowOpen);
        btn.setAttribute('aria-expanded', nowOpen ? 'true' : 'false');
        sessionStorage.setItem(STORAGE_PREFIX + key, nowOpen ? '1' : '0');
      });
    });
  })();

  // Confirm-on-submit for forms with [data-confirm]
  document.addEventListener('submit', (e) => {
    const f = e.target;
    if (f.matches('form[data-confirm]')) {
      const msg = f.getAttribute('data-confirm') || 'Yakin?';
      if (!window.confirm(msg)) e.preventDefault();
    }
  });
})();

// Stage 10 — Bell notifications dropdown
(function () {
  const btn = document.getElementById('btnBell');
  const pop = document.getElementById('bellPop');
  if (!btn || !pop) return;
  const close = () => { pop.classList.remove('is-open'); btn.setAttribute('aria-expanded','false'); pop.setAttribute('aria-hidden','true'); };
  const open  = () => { pop.classList.add('is-open');    btn.setAttribute('aria-expanded','true');  pop.setAttribute('aria-hidden','false'); };
  btn.addEventListener('click', (e) => { e.stopPropagation(); pop.classList.contains('is-open') ? close() : open(); });
  document.addEventListener('click', (e) => { if (!pop.contains(e.target)) close(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
})();
