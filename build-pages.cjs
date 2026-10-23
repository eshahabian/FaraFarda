const fs=require('fs'),vm=require('vm');
const app=fs.readFileSync('dist/app.js','utf8');
const sectors=vm.runInNewContext(app.slice(0,app.indexOf("document.getElementById('sector-list')"))+';sectors');
const slugs=['mining','logistics','trade','energy','textiles','real-estate','agriculture'];
const fields=['ماشین‌آلات و تجهیزات معدنی','حمل‌ونقل و لجستیک','بازرگانی، صادرات و واردات','انرژی، نفت، گاز و پتروشیمی','نساجی و منسوجات','عمران و سرمایه‌گذاری ملکی','کشاورزی و کشت و صنعت'];
const features=[
[['ماشین‌آلات معادن','تأمین ماشین‌آلات متناسب با نیازهای فنی و عملیاتی معادن.'],['خردایش و دانه‌بندی','تأمین خطوط خردایش، سنگ‌شکن، ماسه‌ساز و سرند.'],['فرآوری و انتقال مواد','تجهیزات فرآوری مواد معدنی و نوار نقاله برای واحدهای صنعتی.']],
[['حمل چندوجهی','حمل جاده‌ای، دریایی، هوایی و ریلی متناسب با نیاز محموله.'],['ترانزیت و فورواردینگ','تسهیل جابه‌جایی محموله‌های صنعتی و تجاری میان بازارها.'],['مدیریت زنجیره تأمین','تمرکز بر بهینه‌سازی مسیرها و پیوستگی جریان تأمین.']],
[['صادرات و واردات','تجارت کالا و توسعه ارتباطات در بازارهای منطقه‌ای و جهانی.'],['تأمین صنعتی','تأمین مواد اولیه و محصولات مورد نیاز صنایع.'],['توسعه بازار','شناسایی بازارهای هدف و پیوند تولیدکنندگان، تأمین‌کنندگان و خریداران.']],
[['فرآورده‌های نفتی و گاز','تأمین و تجارت فرآورده‌های نفتی و گاز.'],['محصولات پتروشیمی','تجارت محصولات پتروشیمی و مواد اولیه صنایع انرژی.'],['پیوند عرضه و تقاضا','توسعه همکاری‌های تجاری میان عرضه‌کنندگان و بازارهای مصرف.']],
[['پارچه و منسوجات','تأمین و تجارت انواع پارچه و منسوجات.'],['مواد اولیه نساجی','تأمین مواد اولیه متناسب با نیاز مشتریان تجاری.'],['دکوراسیون و مبلمان','تجارت ملزومات دکوراسیون داخلی و صنعت مبلمان با نگاه توسعه بازار.']],
[['توسعه و ساخت','توسعه پروژه‌های عمرانی و ساختمانی و مشارکت در ساخت.'],['سرمایه‌گذاری ملکی','شناسایی فرصت‌های اقتصادی برای ایجاد ارزش افزوده پایدار.'],['مدیریت املاک','خریدوفروش و مدیریت املاک و مستغلات.']],
[['زراعت، باغداری و کشت و صنعت','سرمایه‌گذاری، توسعه و مشارکت در طرح‌های زراعی، باغی و واحدهای تولیدی.'],['فرآوری و بسته‌بندی','توسعه صنایع تبدیلی و تکمیلی و فرآوری محصولات کشاورزی و غذایی.'],['زنجیره تأمین و عرضه','توسعه عرضه داخلی و بین‌المللی با هدف بهره‌وری منابع و افزایش ارزش افزوده.']]
];
const questions=[
['نوع ماده معدنی و ظرفیت مورد نیاز','تجهیزات یا بخش‌های مورد نیاز خط','مشخصات فنی و محل اجرای پروژه'],
['مبدأ و مقصد محموله','نوع کالا، وزن و ابعاد','زمان‌بندی و شیوه حمل مورد نظر'],
['نوع کالا و مشخصات آن','حجم تأمین و بازار هدف','زمان‌بندی و الزامات تجاری'],
['نوع محصول و مشخصات مورد نیاز','حجم و برنامه تأمین','مبدأ، مقصد و شرایط همکاری'],
['نوع پارچه، منسوجات یا مواد اولیه','مشخصات کیفی و مقدار مورد نیاز','کاربرد محصول و زمان‌بندی تأمین'],
['موقعیت و نوع ملک یا پروژه','مرحله فعلی و ابعاد پروژه','مدل مشارکت یا سرمایه‌گذاری مورد نظر'],
['نوع طرح و موقعیت اجرای آن','منابع و ظرفیت تولید یا فرآوری','مدل سرمایه‌گذاری یا مشارکت مورد نظر']
];
const home=fs.readFileSync('dist/index.html','utf8');
const head=home.slice(0,home.indexOf('<body>'));
let header=home.slice(home.indexOf('<header>'),home.indexOf('</header>')+9).replaceAll('href="#"','href="/"').replaceAll('href="#','href="/#');
const footer=home.slice(home.indexOf('<footer>'),home.indexOf('<script src='));
sectors.forEach((s,i)=>{
 const title=i===6?'کشاورزی، کشت و صنعت و سرمایه‌گذاری‌های کشاورزی':s[2];
 const next=(i+1)%7;
 const html=head.replace(/<title>.*?<\/title>/,`<title>${title} | گروه بین‌المللی فرا</title>`).replace(/name="description" content="[^"]*"/,`name="description" content="${s[4]}"`).replace('href="style.css"','href="/style.css"')+`<body>${header}<main><section class="detail-hero section"><div class="breadcrumb"><a href="/">صفحه اصلی</a><span>/</span><a href="/#sectors">حوزه‌های فعالیت</a><span>/</span><span>${s[0]}</span></div><div class="section-label"><span>حوزه تخصصی ${s[0]}</span><span dir="ltr">${s[3]}</span></div><div class="detail-hero-layout"><div><h1>${title}</h1><p>${s[4]}</p><a class="button" href="/#cooperation?unused" data-cooperation="${i}">آغاز گفت‌وگوی همکاری <span>↙</span></a></div><span class="detail-symbol" aria-hidden="true">${s[1]}</span></div></section><section class="section"><div class="section-label"><span>دامنه فعالیت</span><span dir="ltr">SCOPE OF ACTIVITIES</span></div><h2>تخصصی برای <span>نیاز شما.</span></h2><div class="detail-features">${features[i].map((f,j)=>`<article><b>۰${j+1}</b><h3>${f[0]}</h3><p>${f[1]}</p></article>`).join('')}</div></section><section class="section detail-start"><div><div class="section-label">شروع همکاری</div><h2>از شناخت نیاز،<br>تا تعریف <span>مسیر همکاری.</span></h2><p>برای شروع گفت‌وگو در این حوزه، اطلاعات زیر به روشن‌تر شدن نیاز شما کمک می‌کند.</p></div><ol>${questions[i].map(q=>`<li>${q}</li>`).join('')}</ol></section><section class="section detail-related"><div class="section-label">دیگر حوزه‌های فعالیت فرا</div><div class="related-links">${sectors.map((t,j)=>j===i?'':`<a href="/sectors/${slugs[j]}.html"><span>${t[2]}</span><span>↖</span></a>`).join('')}</div><a class="detail-next" href="/sectors/${slugs[next]}.html">حوزه بعدی: ${sectors[next][2]} ←</a></section></main>${footer.replaceAll('href="#"','href="/"')}<script src="/sector.js"></script></body></html>`;
 fs.mkdirSync('dist/sectors',{recursive:true});
 fs.writeFileSync(`dist/sectors/${slugs[i]}.html`,html.replace('href="/#cooperation?unused"',`href="/?sector=${slugs[i]}#cooperation"`));
});
console.log('Generated 7 sector pages.');
