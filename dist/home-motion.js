(() => {
  const cards = document.querySelectorAll('#sectors > a');
  if (!('IntersectionObserver' in window)) return;
  const observer = new IntersectionObserver(entries => {
    for (const entry of entries) entry.target.classList.toggle('motion-visible', entry.isIntersecting);
  }, { threshold: 0.05 });
  cards.forEach(card => observer.observe(card));
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      cards.forEach(card => { card.classList.remove('motion-visible'); observer.unobserve(card); });
    } else cards.forEach(card => observer.observe(card));
  });
})();
