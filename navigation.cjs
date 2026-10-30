const areas = [
  ['mining', 'تجهیزات صنایع معدنی', 'معرفی شرکت و فعالیت‌های تجهیزات معدنی'],
  ['logistics', 'حمل‌ونقل و لجستیک', 'معرفی خدمات حمل‌ونقل و لجستیک'],
  ['trade', 'صادرات و واردات', 'صادرات و واردات گروه بین‌المللی فرا'],
  ['energy', 'نفت، گاز و پتروشیمی', 'معرفی نفت، گاز و پتروشیمی'],
  ['textiles', 'نساجی و منسوجات'],
  ['real-estate', 'عمران و سرمایه‌گذاری ملکی'],
  ['agriculture', 'کشاورزی و کشت و صنعت', 'معرفی کشاورزی و کشت و صنعت']
];

function renderHeader(base = '', current = 'home') {
  return `<header class="fara-header" data-navigation>
  <div class="fara-mainbar">
    <a class="fara-brand" href="${base}index.html" aria-label="گروه بین‌المللی فرا، صفحه اصلی"><img src="${base}assets/logo.png" alt="نشان فرا" width="82" height="44"><span>گروه بین‌المللی فرا<small lang="en" dir="ltr">FARA INTERNATIONAL GROUP</small></span></a>
    <button class="fara-menu-toggle" type="button" aria-controls="fara-primary" aria-expanded="false"><span class="fara-menu-lines" aria-hidden="true"></span><span data-menu-label>منو</span></button>

  </div>
  <nav class="fara-sector-strip" id="fara-primary" aria-label="منوی اصلی"><div><a href="${base}index.html"${current === 'home' ? ' aria-current="page"' : ''}>صفحهٔ اصلی</a>${areas.map(([slug, title]) => `<a href="${base}sectors/${slug}.html"${current === slug ? ' aria-current="page"' : ''}>${title}</a>`).join('')}</div></nav>
</header>`;
}
module.exports = { renderHeader, areas };
