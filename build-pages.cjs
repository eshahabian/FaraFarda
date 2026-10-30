const fs = require('fs');
const vm = require('vm');
const { renderHeader } = require('./navigation.cjs');
const { renderFooter } = require('./footer.cjs');
const { renderProfile } = require('./profile-content.cjs');

const app = fs.readFileSync('dist/app.js', 'utf8');
const sectors = vm.runInNewContext(
  app.slice(0, app.indexOf("document.getElementById('sector-list')")) + ';sectors'
);
const slugs = ['mining', 'logistics', 'trade', 'energy', 'textiles', 'real-estate', 'agriculture'];
const shortTitles = [
  'تجهیزات صنایع معدنی',
  'حمل‌ونقل و لجستیک',
  'صادرات و واردات',
  'نفت، گاز و پتروشیمی',
  'نساجی و منسوجات',
  'عمران و سرمایه‌گذاری ملکی',
  'کشاورزی و کشت و صنعت'
];
const headlines = [
  'تجهیزات متناسب با نیاز عملیاتی معادن.',
  'جابه‌جایی قابل اتکا در مسیرهای بین‌المللی.',
  'پیوند تولیدکننده، تأمین‌کننده و بازار.',
  'عرضه انرژی و مواد پتروشیمی به بازارهای هدف.',
  'تأمین منسوجات با نگاه توسعه بازار.',
  'ساخت و سرمایه‌گذاری با ارزش افزوده پایدار.',
  'از کشت تا فرآوری؛ زنجیره ارزش کشاورزی.'
];
const intros = [
  'از تأمین ماشین‌آلات تا تجهیزات فرآوری، مسیر همکاری را بر اساس مشخصات فنی پروژه و ظرفیت عملیاتی معدن تعریف می‌کنیم.',
  'مسیر، شیوه حمل و زمان‌بندی را با نوع محموله هماهنگ می‌کنیم تا جریان تأمین پیوسته و قابل پیش‌بینی بماند.',
  'نیاز بازار را به تأمین واقعی وصل می‌کنیم؛ از شناسایی فرصت تا شکل‌گیری همکاری تجاری پایدار.',
  'عرضه فرآورده‌های انرژی و پتروشیمی را با تقاضای بازار هدف هم‌راستا می‌کنیم.',
  'تأمین پارچه و مواد اولیه را بر اساس کیفیت مورد نیاز و برنامه زمانی مشتری پیش می‌بریم.',
  'فرصت‌های عمرانی و ملکی را با مدل مشارکت مناسب و نگاه بلندمدت ارزش‌آفرینی می‌کنیم.',
  'از توسعه کشت تا فرآوری و عرضه، زنجیره ارزش کشاورزی را یکپارچه پیش می‌بریم.'
];
const features = [
  [
    ['ماشین‌آلات معادن', 'تأمین ماشین‌آلات متناسب با نیازهای فنی و عملیاتی معادن.'],
    ['خردایش و دانه‌بندی', 'تأمین خطوط خردایش، سنگ‌شکن، ماسه‌ساز و سرند.'],
    ['فرآوری و انتقال مواد', 'تجهیزات فرآوری مواد معدنی و نوار نقاله برای واحدهای صنعتی.']
  ],
  [
    ['حمل چندوجهی', 'حمل جاده‌ای، دریایی، هوایی و ریلی متناسب با نیاز محموله.'],
    ['ترانزیت و فورواردینگ', 'تسهیل جابه‌جایی محموله‌های صنعتی و تجاری میان بازارها.'],
    ['مدیریت زنجیره تأمین', 'تمرکز بر بهینه‌سازی مسیرها و پیوستگی جریان تأمین.']
  ],
  [
    ['صادرات و واردات', 'تجارت کالا و توسعه ارتباطات در بازارهای منطقه‌ای و جهانی.'],
    ['تأمین صنعتی', 'تأمین مواد اولیه و محصولات مورد نیاز صنایع.'],
    ['توسعه بازار', 'شناسایی بازارهای هدف و پیوند تولیدکنندگان، تأمین‌کنندگان و خریداران.']
  ],
  [
    ['فرآورده‌های نفتی و گاز', 'تأمین و تجارت فرآورده‌های نفتی و گاز.'],
    ['محصولات پتروشیمی', 'تجارت محصولات پتروشیمی و مواد اولیه صنایع انرژی.'],
    ['پیوند عرضه و تقاضا', 'توسعه همکاری‌های تجاری میان عرضه‌کنندگان و بازارهای مصرف.']
  ],
  [
    ['پارچه و منسوجات', 'تأمین و تجارت انواع پارچه و منسوجات.'],
    ['مواد اولیه نساجی', 'تأمین مواد اولیه متناسب با نیاز مشتریان تجاری.'],
    ['دکوراسیون و مبلمان', 'تجارت ملزومات دکوراسیون داخلی و صنعت مبلمان با نگاه توسعه بازار.']
  ],
  [
    ['توسعه و ساخت', 'توسعه پروژه‌های عمرانی و ساختمانی و مشارکت در ساخت.'],
    ['سرمایه‌گذاری ملکی', 'شناسایی فرصت‌های اقتصادی برای ایجاد ارزش افزوده پایدار.'],
    ['مدیریت املاک', 'خریدوفروش و مدیریت املاک و مستغلات.']
  ],
  [
    ['زراعت، باغداری و کشت و صنعت', 'سرمایه‌گذاری، توسعه و مشارکت در طرح‌های زراعی، باغی و واحدهای تولیدی.'],
    ['فرآوری و بسته‌بندی', 'توسعه صنایع تبدیلی و تکمیلی و فرآوری محصولات کشاورزی و غذایی.'],
    ['زنجیره تأمین و عرضه', 'توسعه عرضه داخلی و بین‌المللی با هدف بهره‌وری منابع و افزایش ارزش افزوده.']
  ]
];
const questions = [
  ['نوع ماده معدنی و ظرفیت مورد نیاز', 'تجهیزات یا بخش‌های مورد نیاز خط', 'مشخصات فنی و محل اجرای پروژه'],
  ['مبدأ و مقصد محموله', 'نوع کالا، وزن و ابعاد', 'زمان‌بندی و شیوه حمل مورد نظر'],
  ['نوع کالا و مشخصات آن', 'حجم تأمین و بازار هدف', 'زمان‌بندی و الزامات تجاری'],
  ['نوع محصول و مشخصات مورد نیاز', 'حجم و برنامه تأمین', 'مبدأ، مقصد و شرایط همکاری'],
  ['نوع پارچه، منسوجات یا مواد اولیه', 'مشخصات کیفی و مقدار مورد نیاز', 'کاربرد محصول و زمان‌بندی تأمین'],
  ['موقعیت و نوع ملک یا پروژه', 'مرحله فعلی و ابعاد پروژه', 'مدل مشارکت یا سرمایه‌گذاری مورد نظر'],
  ['نوع طرح و موقعیت اجرای آن', 'منابع و ظرفیت تولید یا فرآوری', 'مدل سرمایه‌گذاری یا مشارکت مورد نظر']
];

fs.mkdirSync('dist/sectors', { recursive: true });

sectors.forEach((s, i) => {
  const title = i === 6 ? 'کشاورزی، کشت و صنعت و سرمایه‌گذاری‌های کشاورزی' : s[2];
  const profile = renderProfile(slugs[i]);
  const next = (i + 1) % 7;
  const trust = features[i].map((f) => `<span>${f[0]}</span>`).join('');
  const services = features[i]
    .map(
      (f, j) =>
        `<article><b>۰${j + 1}</b><h3>${f[0]}</h3><p>${f[1]}</p></article>`
    )
    .join('');
  const qs = questions[i]
    .map((q, j) => `<li><span>۰${j + 1}</span>${q}</li>`)
    .join('');
  const related = sectors
    .map((t, j) => {
      if (j === i) return '';
      const label = shortTitles[j];
      return `<a href="${slugs[j]}.html"><span class="thumb"><img src="../assets/${slugs[j]}.webp" alt="${label}" width="840" height="472" loading="lazy" decoding="async"></span><strong>${label}</strong></a>`;
    })
    .join('');

  const html = `<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#021329">
<meta name="description" content="${s[4]}">
<title>${title} | گروه بین‌المللی فرا</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../sector.css?v=3">
<link rel="stylesheet" href="../navigation.css?v=1">
<link rel="stylesheet" href="../footer.css?v=1">
<link rel="stylesheet" href="../profile.css?v=1">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon-32.png">
<link rel="icon" href="../favicon.ico">
<link rel="apple-touch-icon" href="../assets/favicon-180.png">
</head>
<body>
${renderHeader('../', slugs[i])}

<main>
  <section class="sector-hero" aria-label="${title}">
    <div class="sector-hero-media" style="background-image:url('../assets/${slugs[i]}.webp')" role="img" aria-label="${shortTitles[i]}"></div>
    <div class="sector-hero-inner">
      <div class="breadcrumb">
        <a href="../index.html">صفحه اصلی</a><span>/</span>
        <a href="../index.html#sectors">حوزه‌های فعالیت</a><span>/</span>
        <span>${s[0]}</span>
      </div>
      <div class="sector-kicker">
        <span>حوزه تخصصی ${s[0]}</span>
        <span dir="ltr">${s[3]}</span>
      </div>
      <h1>${title}</h1>
      <p class="lead">${s[4]}</p>
      <div class="hero-actions">
        <a class="button" href="#start">آغاز گفت‌وگوی همکاری <span>↙</span></a>
        <a class="button-ghost" href="#${profile ? 'profile' : 'services'}">معرفی و شرح فعالیت‌ها</a>
      </div>
      <div class="trust-row">${trust}</div>
    </div>
  </section>

  ${profile || `<section class="section sector-intro">
    <div class="copy">
      <div class="section-label"><span>نگاه عملیاتی</span><span dir="ltr">HOW WE WORK</span></div>
      <h2>${headlines[i]}</h2>
      <p>${intros[i]}</p>
    </div>
    <figure class="intro-figure">
      <img src="../assets/${slugs[i]}.webp" alt="${shortTitles[i]}" width="1200" height="900" loading="lazy" decoding="async">
      <figcaption class="intro-caption">${shortTitles[i]} · گروه بین‌المللی فرا</figcaption>
    </figure>
  </section>

  <section class="section sector-services" id="services">
    <div class="head">
      <div class="section-label"><span>دامنه فعالیت</span><span dir="ltr">SCOPE OF ACTIVITIES</span></div>
      <h2>خدمات این حوزه، <em>متناسب با نیاز شما.</em></h2>
      <p>سه محور اصلی فعالیت در این بخش؛ برای تعریف دقیق‌تر همکاری، از بخش شروع گفت‌وگو استفاده کنید.</p>
    </div>
    <div class="service-grid">${services}</div>
  </section>`}

  <section class="section sector-start" id="start">
    <div class="copy">
      <div class="section-label">شروع همکاری</div>
      <h2>از شناخت نیاز، تا تعریف <em>مسیر همکاری.</em></h2>
      <p>برای شروع گفت‌وگو در این حوزه، اطلاعات زیر به روشن‌تر شدن نیاز شما کمک می‌کند.</p>
    </div>
    <ol>${qs}</ol>
  </section>

  <section class="section sector-related">
    <div class="section-label">دیگر حوزه‌های فعالیت فرا</div>
    <div class="related-grid">${related}</div>
    <a class="detail-next" href="${slugs[next]}.html">حوزه بعدی: ${sectors[next][2]} ←</a>
  </section>
</main>

${renderFooter('../')}
<script src="../navigation.js" defer></script>
</body>
</html>`;

  fs.writeFileSync(`dist/sectors/${slugs[i]}.html`, html);
});

console.log('Generated 7 sector pages (Maersk-inspired).');
