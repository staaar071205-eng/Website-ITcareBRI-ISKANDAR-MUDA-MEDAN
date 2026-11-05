/* ITcare BRI — interaksi landing: reveal saat scroll, parallax, hitung angka, progres alur, tilt kartu */
(() => {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const touch = window.matchMedia('(hover: none)').matches;
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));
  const pad = (n, p) => String(n).padStart(p || 0, '0');

  /* ---------- Hitung angka (count-up) ---------- */
  const counters = $$('[data-count]');
  counters.forEach((el) => { if (!reduce) el.textContent = pad(0, +el.dataset.pad || 0); });
  const runCounters = (root) => {
    const list = root.matches && root.matches('[data-count]') ? [root] : $$('[data-count]', root);
    list.forEach((el) => {
      if (el.dataset.done) return; el.dataset.done = '1';
      const target = +el.dataset.count, p = +el.dataset.pad || 0;
      if (reduce) { el.textContent = pad(target, p); return; }
      const t0 = performance.now(), dur = 1400;
      const tick = (t) => {
        const k = clamp((t - t0) / dur);
        el.textContent = pad(Math.round(target * (1 - Math.pow(1 - k, 3))), p);
        if (k < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    });
  };

  /* ---------- Reveal saat elemen masuk layar ---------- */
  const reveals = $$('[data-reveal]');
  reveals.forEach((el) => el.style.setProperty('--d', el.dataset.delay || 0));
  if (reduce || !('IntersectionObserver' in window)) {
    reveals.forEach((el) => { el.classList.add('in'); runCounters(el); });
  } else {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (!en.isIntersecting) return;
        en.target.classList.add('in');
        runCounters(en.target);
        io.unobserve(en.target);
      });
    }, { threshold: 0.14, rootMargin: '0px 0px -6% 0px' });
    reveals.forEach((el) => io.observe(el));
  }

  /* ---------- Scroll: progress bar, header, parallax, alur ---------- */
  const bar = document.getElementById('scrollBar');
  const header = document.getElementById('siteHeader');
  const parallax = $$('[data-parallax]');
  const steps = document.getElementById('steps');
  const stepItems = steps ? $$('.step', steps) : [];
  let ticking = false;

  const update = () => {
    ticking = false;
    const y = window.scrollY, vh = window.innerHeight;
    const max = document.documentElement.scrollHeight - vh;
    if (bar) bar.style.transform = 'scaleX(' + (max > 0 ? clamp(y / max) : 0) + ')';
    if (header) header.classList.toggle('scrolled', y > 24);

    if (!reduce) parallax.forEach((el) => {
      const r = el.parentElement.getBoundingClientRect();
      if (r.bottom < -300 || r.top > vh + 300) return;
      const center = r.top + r.height / 2 - vh / 2;
      el.style.translate = '0 ' + (-center * parseFloat(el.dataset.parallax)).toFixed(1) + 'px';
    });

    if (steps) {
      const r = steps.getBoundingClientRect();
      const p = reduce ? 1 : clamp((vh * 0.78 - r.top) / Math.max(r.height, 1));
      steps.style.setProperty('--p', p.toFixed(3));
      stepItems.forEach((s, i) => s.classList.toggle('active', p > 0 && p >= i / (stepItems.length - 1) - 0.001));
    }
  };
  const onScroll = () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } };
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  update();

  /* ---------- Tilt kartu saat kursor bergerak ---------- */
  if (!reduce && !touch) {
    $$('[data-tilt]').forEach((el) => {
      const isDash = el.classList.contains('dash');
      const max = isDash ? 7 : 5;
      el.addEventListener('mousemove', (e) => {
        const r = el.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
        el.style.setProperty('--ry', (x * max * 2 + (isDash ? -4 : 0)).toFixed(2) + 'deg');
        el.style.setProperty('--rx', (-y * max * 2 + (isDash ? 2 : 0)).toFixed(2) + 'deg');
      });
      el.addEventListener('mouseleave', () => { el.style.removeProperty('--rx'); el.style.removeProperty('--ry'); });
    });
  }
})();
