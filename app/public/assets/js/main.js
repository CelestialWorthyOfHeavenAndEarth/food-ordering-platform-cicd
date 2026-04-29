// ============================================
// FEASTLY — MAIN JAVASCRIPT
// ============================================

// ---- Scroll Reveal ----
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target); // Trigger once only
    }
  });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

// ---- Sticky Nav ----
const nav = document.getElementById('mainNav');
window.addEventListener('scroll', () => {
  nav?.classList.toggle('scrolled', window.scrollY > 60);
}, { passive: true });

// ---- Mobile Menu ----
document.getElementById('mobileMenuToggle')?.addEventListener('click', () => {
  document.getElementById('mobileMenu')?.classList.toggle('open');
});

// ---- User Dropdown ----
document.getElementById('userMenuToggle')?.addEventListener('click', (e) => {
  e.stopPropagation();
  document.getElementById('userDropdown')?.classList.toggle('open');
});
document.addEventListener('click', () => {
  document.getElementById('userDropdown')?.classList.remove('open');
});

// ---- Toast System ----
const Toast = (() => {
  function show(message, type = 'info', duration = 3000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    const icons = { 
      success: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>', 
      error: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>', 
      info: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>' 
    };
    toast.innerHTML = `<span style="display:flex;align-items:center;">${icons[type] || ''}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), duration + 300);
  }
  return { show };
})();

// Expose globally
window.Toast = Toast;

// ---- Smooth anchor scroll ----
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function(e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

// ---- Add animate classes on scroll items ----
document.querySelectorAll('.stagger > *').forEach((el, i) => {
  el.classList.add('animate-fade-up');
  el.style.animationDelay = `${i * 0.07}s`;
  el.style.animationFillMode = 'both';
});
