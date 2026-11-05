/* ITcare BRI — interaksi halaman login */
document.addEventListener('DOMContentLoaded', () => {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Tampilkan / sembunyikan password
  const toggle = document.getElementById('togglePassword');
  const pass = document.getElementById('password');
  if (toggle && pass) toggle.addEventListener('click', () => {
    const show = pass.type === 'password';
    pass.type = show ? 'text' : 'password';
    toggle.textContent = show ? 'Sembunyikan' : 'Tampilkan';
    toggle.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
  });

  // Animasi tombol saat mengirim
  const form = document.getElementById('loginForm');
  const btn = document.getElementById('submitBtn');
  if (form && btn) form.addEventListener('submit', () => {
    btn.classList.add('loading');
    btn.querySelector('.lbl').textContent = 'Memeriksa…';
  });

  // Reveal bertahap (saat dimuat, dan saat di-scroll pada layar kecil)
  const items = Array.from(document.querySelectorAll('[data-reveal]'));
  items.forEach((el) => el.style.setProperty('--d', el.dataset.delay || 0));
  if (reduce || !('IntersectionObserver' in window)) { items.forEach((el) => el.classList.add('in')); }
  else {
    const io = new IntersectionObserver((es) => es.forEach((en) => {
      if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
    }), { threshold: 0.1 });
    items.forEach((el) => io.observe(el));
  }

  // Efek kedalaman mengikuti gerakan kursor pada panel kiri
  const showcase = document.getElementById('showcase');
  if (showcase && !reduce && !window.matchMedia('(hover: none)').matches) {
    const layers = Array.from(showcase.querySelectorAll('[data-depth]')).filter((el) => !el.hasAttribute('data-reveal'));
    window.addEventListener('mousemove', (e) => {
      const x = e.clientX / window.innerWidth - 0.5, y = e.clientY / window.innerHeight - 0.5;
      layers.forEach((el) => {
        const d = parseFloat(el.dataset.depth);
        el.style.translate = (x * d * 2).toFixed(1) + 'px ' + (y * d * 2).toFixed(1) + 'px';
      });
    });
  }
});
