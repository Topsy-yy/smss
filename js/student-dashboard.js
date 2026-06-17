/* ============================================================
   student-dashboard.js
   Enhanced UX for all student-facing pages
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ── 1. TAB SWITCHING ──────────────────────────────────── */
  const tabBtns     = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');

  const params              = new URLSearchParams(window.location.search);
  const forceApplicationsTab = params.get('tab') === 'applications' || params.has('app_filter');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => b.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      const target = document.getElementById(btn.getAttribute('data-target'));
      if (target) {
        target.classList.add('active');
        triggerCardAnimations(target);
      }
    });
  });

  if (forceApplicationsTab) {
    const appTab = document.querySelector('.tab-btn[data-target="tab-applications"]');
    if (appTab) appTab.click();
  } else {
    const activeTab = document.querySelector('.tab-content.active');
    if (activeTab) triggerCardAnimations(activeTab);
  }

  /* ── 2. CARD STAGGER ANIMATIONS ───────────────────────── */
  function triggerCardAnimations(container) {
    const cards = container.querySelectorAll('.opp-card, .app-card');
    cards.forEach((card, i) => {
      card.classList.remove('animate-in');
      setTimeout(() => card.classList.add('animate-in'), i * 55);
    });
  }

  /* Run on page load for cards already visible */
  document.querySelectorAll('.opp-card, .app-card').forEach((card, i) => {
    setTimeout(() => card.classList.add('animate-in'), i * 55);
  });

  /* ── 3. SEARCH & FILTER ────────────────────────────────── */
  const searchInput     = document.getElementById('searchOpps');
  const filterPills     = document.querySelectorAll('.filter-pill');
  const oppCards        = document.querySelectorAll('.opp-card');
  const emptyStateOpps  = document.getElementById('emptyStateOpps');

  let currentFilter = 'all';
  let searchQuery   = '';

  function filterOpportunities() {
    let visible = 0;
    oppCards.forEach(card => {
      const title    = (card.getAttribute('data-title') || '').toLowerCase();
      const org      = (card.getAttribute('data-org')   || '').toLowerCase();
      const category = (card.getAttribute('data-category') || '').toLowerCase();
      const matchesSearch = title.includes(searchQuery) || org.includes(searchQuery);
      const matchesFilter = currentFilter === 'all' || category === currentFilter;
      const show = matchesSearch && matchesFilter;
      card.style.display = show ? 'flex' : 'none';
      if (show) {
        card.classList.remove('animate-in');
        setTimeout(() => card.classList.add('animate-in'), visible * 50);
        visible++;
      }
    });
    if (emptyStateOpps) {
      emptyStateOpps.style.display = visible === 0 ? 'block' : 'none';
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', e => {
      searchQuery = e.target.value.toLowerCase().trim();
      filterOpportunities();
    });
  }

  filterPills.forEach(pill => {
    pill.addEventListener('click', () => {
      filterPills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      currentFilter = pill.getAttribute('data-filter') || 'all';
      filterOpportunities();
    });
  });

  /* ── 4. BROWSE OPPORTUNITIES BUTTON ───────────────────── */
  const browseBtn = document.getElementById('btnBrowseOpps');
  if (browseBtn) {
    browseBtn.addEventListener('click', () => {
      const oppTabBtn = document.querySelector('.tab-btn[data-target="tab-opportunities"]');
      if (oppTabBtn) oppTabBtn.click();
      window.scrollTo({ top: 0, behavior: 'smooth' });
      if (searchInput) searchInput.focus();
    });
  }

  /* ── 5. PROFILE EDIT TOGGLE (smooth transition) ─────── */
  const showDivButton = document.getElementById('showDivButton');
  const editDiv       = document.getElementById('editDiv');
  const displayDiv    = document.getElementById('display');

  if (showDivButton && editDiv && displayDiv) {
    showDivButton.addEventListener('click', e => {
      e.preventDefault();

      /* Fade out display panel */
      displayDiv.style.transition = 'opacity .3s ease, transform .3s ease';
      displayDiv.style.opacity    = '0';
      displayDiv.style.transform  = 'translateY(-8px)';

      setTimeout(() => {
        displayDiv.style.display = 'none';

        /* Fade in edit panel */
        editDiv.style.display   = 'block';
        editDiv.style.opacity   = '0';
        editDiv.style.transform = 'translateY(12px)';
        editDiv.style.transition = 'opacity .4s ease, transform .4s ease';

        /* Force reflow */
        void editDiv.offsetWidth;

        editDiv.style.opacity   = '1';
        editDiv.style.transform = 'translateY(0)';
      }, 280);
    });
  }

  /* ── 6. PROGRESS BAR ANIMATION ──────────────────────── */
  const fill = document.querySelector('.progress-bar-fill');
  if (fill) {
    const target = fill.style.width;
    fill.style.width = '0%';
    setTimeout(() => { fill.style.width = target; }, 400);
  }

  /* ── 7. STAGGER ITEM ANIMATION ──────────────────────── */
  document.querySelectorAll('table tbody tr, .form-group, section > h1, section > p').forEach((el, i) => {
    el.classList.add('stagger-item');
    el.style.animationDelay = `${Math.min(i * 0.045, 1.2)}s`;
  });

  /* ── 8. RIPPLE EFFECT ON BUTTONS ────────────────────── */
  /* Inject ripple keyframe once */
  if (!document.getElementById('sdb-ripple-style')) {
    const s = document.createElement('style');
    s.id = 'sdb-ripple-style';
    s.textContent = '@keyframes ripple { to { transform: translate(-50%,-50%) scale(4); opacity: 0; } }';
    document.head.appendChild(s);
  }

  document.querySelectorAll(
    'input[type="submit"], .btn-solid, .btn-outline, .btn-primary, button.btn-primary'
  ).forEach(btn => {
    btn.addEventListener('mousedown', function(e) {
      const rect   = e.currentTarget.getBoundingClientRect();
      const ripple = document.createElement('span');
      Object.assign(ripple.style, {
        position:     'absolute',
        background:   'rgba(255,255,255,.38)',
        width:        '110px',
        height:       '110px',
        borderRadius: '50%',
        transform:    'translate(-50%,-50%) scale(0)',
        animation:    'ripple .55s linear',
        left:         `${e.clientX - rect.left}px`,
        top:          `${e.clientY - rect.top}px`,
        pointerEvents:'none',
      });
      if (window.getComputedStyle(this).position === 'static') {
        this.style.position = 'relative';
        this.style.overflow = 'hidden';
      }
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 560);
    });
  });

  /* ── 9. FILE UPLOAD UX ───────────────────────────────── */
  document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
      const zone  = this.closest('.upload-zone');
      const label = this.nextElementSibling;
      if (!this.files || !this.files.length) return;
      const name = this.files[0].name;
      if (label) {
        label.innerHTML = `<span style="color:var(--success)">
          <svg style="width:15px;vertical-align:-3px;margin-right:5px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <strong>Ready:</strong> ${name}
        </span>`;
      }
      if (zone) {
        zone.style.borderColor = 'var(--success)';
        zone.style.background  = 'var(--success-bg)';
      }
    });
  });

  /* ── 10. FILE VALIDATION ─────────────────────────────── */
  window.fileValidation = function(inputId) {
    const input = document.getElementById(inputId);
    if (!input || !input.files || !input.files.length) return true;
    const file = input.files[0];
    if (file.type !== 'application/pdf') {
      showToast('Only PDF files are accepted.', 'error');
      input.value = '';
      return false;
    }
    if (file.size > 8_000_000) {
      showToast('File exceeds the 8 MB limit.', 'error');
      input.value = '';
      return false;
    }
    return true;
  };

  /* ── 11. TOAST NOTIFICATIONS ─────────────────────────── */
  function showToast(message, type = 'info') {
    let container = document.getElementById('sdb-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'sdb-toast-container';
      Object.assign(container.style, {
        position:   'fixed',
        bottom:     '1.5rem',
        right:      '1.5rem',
        zIndex:     '9999',
        display:    'flex',
        flexDirection: 'column',
        gap:        '.6rem',
        pointerEvents: 'none',
      });
      document.body.appendChild(container);
    }

    const colors = {
      success: { bg: '#dcfce7', border: '#86efac', text: '#15803d' },
      error:   { bg: '#fee2e2', border: '#fca5a5', text: '#dc2626' },
      info:    { bg: '#eff6ff', border: '#93c5fd', text: '#1d4ed8' },
    };
    const c = colors[type] || colors.info;

    const toast = document.createElement('div');
    Object.assign(toast.style, {
      background:   c.bg,
      border:       `1px solid ${c.border}`,
      color:        c.text,
      padding:      '.75rem 1.1rem',
      borderRadius: '10px',
      fontSize:     '.88rem',
      fontWeight:   '600',
      boxShadow:    '0 4px 12px rgba(0,0,0,.1)',
      maxWidth:     '320px',
      opacity:      '0',
      transform:    'translateY(10px)',
      transition:   'all .25s ease',
      pointerEvents:'auto',
    });
    toast.textContent = message;
    container.appendChild(toast);

    /* Animate in */
    void toast.offsetWidth;
    toast.style.opacity   = '1';
    toast.style.transform = 'translateY(0)';

    /* Auto dismiss */
    setTimeout(() => {
      toast.style.opacity   = '0';
      toast.style.transform = 'translateY(8px)';
      setTimeout(() => toast.remove(), 280);
    }, 3500);
  }

  /* Expose for use from inline PHP alert replacements */
  window.showToast = showToast;

  /* ── 12. NAV ACTIVE STATE FROM URL ──────────────────── */
  const currentPath = window.location.pathname.split('/').pop();
  document.querySelectorAll('.app-nav ul li a').forEach(link => {
    const href = link.getAttribute('href');
    if (href && href !== '#' && currentPath && href.includes(currentPath)) {
      link.closest('li')?.classList.add('current');
    }
  });

  /* ── 13. SMOOTH SCROLL FOR IN-PAGE LINKS ─────────────── */
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* ── 14. TABLE ROW CLICK PASS-THROUGH ───────────────── */
  /* If a <tr> contains exactly one link or button, clicking anywhere on
     the row triggers it — improves tap targets on mobile */
  document.querySelectorAll('table.table tbody tr').forEach(row => {
    const links = row.querySelectorAll('a, button[name="view"]');
    if (links.length === 1) {
      row.style.cursor = 'pointer';
      row.addEventListener('click', e => {
        if (!e.target.closest('a, button, form')) links[0].click();
      });
    }
  });

});