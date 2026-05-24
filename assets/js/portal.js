// SMIA2 Portal JavaScript
document.addEventListener('DOMContentLoaded', () => {

  // Navbar scroll effect
  const nav = document.getElementById('mainNav');
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 50);
    document.getElementById('scrollTop').style.display = window.scrollY > 400 ? 'flex' : 'none';
  });

  // Scroll to top
  document.getElementById('scrollTop')?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // Animated counters
  const counters = document.querySelectorAll('.counter-num');
  const speed = 2000;
  const animateCounter = (el) => {
    const target = +el.dataset.target;
    const step = target / (speed / 16);
    let current = 0;
    const timer = setInterval(() => {
      current += step;
      el.textContent = Math.min(Math.floor(current), target);
      if (current >= target) clearInterval(timer);
    }, 16);
  };

  // Intersection observer for counters and animations
  const obs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        if (entry.target.classList.contains('counter-num')) animateCounter(entry.target);
        if (entry.target.classList.contains('animate-on-scroll')) entry.target.classList.add('visible');
        obs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });

  counters.forEach(el => obs.observe(el));
  document.querySelectorAll('.animate-on-scroll').forEach(el => obs.observe(el));

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const target = document.querySelector(a.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
});
