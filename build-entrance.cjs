const fs=require('node:fs'),path=require('node:path');
const { renderHeader } = require('./navigation.cjs');
const fields=[
['mining','ماشین‌آلات و تجهیزات معدنی','تأمین ماشین‌آلات معادن، خطوط خردایش و تجهیزات فرآوری؛ متناسب با نیازهای فنی و عملیاتی هر پروژه.','sectors/mining.html','M8 54h40l5 7H5z M13 49V35h16v14 M17 35v-9h10l6 9 M29 41h9l9-25 10-5 9 29-5 3-10-22-9 26H29 M54 51l9-8 10 13H58 M15 57h25'],
['logistics','حمل‌ونقل و لجستیک بین‌المللی','حمل جاده‌ای، دریایی، هوایی و ریلی، ترانزیت و مدیریت زنجیره تأمین؛ از مبدأ تا بازار هدف.','logistics.html','M40 11a23 23 0 1 0 0 46 M17 34h40 M40 11c-15 14-15 32 0 46 M40 11c8 8 12 15 12 23 M40 11v46 M22 20h34 M21 47h15 M42 40h20v18H42z M62 46h9l7 9v3H62 M49 65a5 5 0 1 0 0-10 5 5 0 0 0 0 10 M70 65a5 5 0 1 0 0-10 5 5 0 0 0 0 10'],
['trade','بازرگانی، صادرات و واردات','تأمین کالا و مواد اولیه و پیوند تولیدکنندگان، تأمین‌کنندگان و خریداران در بازارهای منطقه‌ای و جهانی.','sectors/trade.html','M39 10a25 25 0 1 0 22 38 M14 35h50 M39 10c-17 15-17 35 0 50 M39 10c10 10 14 20 13 29 M39 10v50 M19 22h40 M18 48h23 M52 49h22l-7-7 M74 49l-7 7 M68 64H46l7 7 M46 64l7-7'],
['energy','نفت، گاز و پتروشیمی','تأمین و تجارت فرآورده‌های نفتی، گاز، محصولات پتروشیمی و مواد اولیه مورد نیاز صنایع انرژی.','sectors/energy.html','M8 66h65 M13 66V43h16v23 M18 43V24h6v19 M35 66V17h9v49 M38 17V7h3v10 M31 30h18 M31 43h18 M31 54h18 M51 66V45h17v21 M56 45V32 M60 34c-15-10 4-22 2-28 17 18 12 25-2 28 M18 50h6 M18 58h6 M56 53h7 M56 60h7'],
['textiles','نساجی و منسوجات','تأمین پارچه، منسوجات و مواد اولیه نساجی، همراه با ملزومات دکوراسیون داخلی و صنعت مبلمان.','sectors/textiles.html','M25 14c-18 0-18 27 0 27s18-27 0-27 M25 20c-10 0-10 15 0 15s10-15 0-15 M25 26v3 M25 14h36c15 0 19 20 10 25 M23 42c-18 0-18 27 0 27s18-27 0-27 M23 48c-10 0-10 15 0 15s10-15 0-15 M23 54v3 M23 42h36c18 0 18 27 0 27H23 M39 58l35-10 M39 64l35-10'],
['real-estate','عمران و سرمایه‌گذاری ملکی','توسعه پروژه‌های ساختمانی، مشارکت در ساخت و سرمایه‌گذاری ملکی با نگاه به ارزش‌آفرینی پایدار.','sectors/real-estate.html','M8 67h65 M15 67V36l16-9v40 M31 67V15l19-9v61 M50 27l15 9v31 M37 18v42 M43 15v45 M20 40v4 M25 37v7 M20 50v4 M25 50v4 M56 39v5 M60 41v5 M56 51v5 M60 53v5'],
['agriculture','کشاورزی و کشت و صنعت','توسعه طرح‌های زراعی و باغی، فرآوری و بسته‌بندی محصولات و تکمیل زنجیره ارزش کشاورزی.','sectors/agriculture.html','M40 66V20 M40 42C18 43 13 32 12 21c18-1 28 7 28 21 M40 53c23 1 29-12 29-24-18 0-29 9-29 24 M40 28c-12-4-13-15 0-23 13 8 12 19 0 23 M9 69c19-9 43-9 64 0 M24 68l-8 7 M55 68l8 7'],
['group','تجارت، صنعت و سرمایه‌گذاری','بررسی فرصت‌های تجاری و صنعتی و تعریف مسیرهای مشارکت میان صاحبان طرح، تولیدکنندگان و سرمایه‌گذاران.','investment.html','M10 65h64 M15 59V46h10v13 M34 59V35h10v24 M53 59V23h10v36 M13 32l19-13 14 4L66 8 M54 8h12v12']
];
const logo=`<a class="brand" href="index.html" aria-label="گروه بین‌المللی فرا، صفحه اصلی"><img src="assets/logo.png" width="96" height="60" alt="لوگو فرا"><span>گروه بین‌المللی فرا<small lang="en" dir="ltr">FARA INTERNATIONAL GROUP</small></span></a>`;
function buildEntrance(){
const cards=fields.map(([id,title,copy,href,d],i)=>`<a class="activity" data-sector="${id}" href="${href}"><span class="activity-icon"><svg viewBox="0 0 84 84" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="${d}"/></svg></span><h3>${title}</h3></a>`).join('');
const html=`<!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#04172d"><meta name="description" content="گروه بین‌المللی فرا؛ فعالیت در تجارت، صنعت، لجستیک و سرمایه‌گذاری. فراتر از مرزها، هم‌مسیر با آینده."><title>گروه بین‌المللی فرا | فراتر از مرزها، هم‌مسیر با آینده</title><link rel="icon" href="favicon.ico"><link rel="stylesheet" href="navigation.css?v=1"><link rel="stylesheet" href="entrance.css?v=16"></head><body>
<a class="skip" href="#main">رفتن به محتوای اصلی</a>${renderHeader()}
<main id="main"><section class="brand-hero" aria-labelledby="group-title"><div class="brand-hero-scene"><div class="brand-hero-heading"><h1 id="group-title">گروه بین‌المللی فرا</h1><div class="brand-hero-en" lang="en" dir="ltr"><span>FARA INTERNATIONAL GROUP</span></div><p class="brand-hero-slogan">فراتر از مرزها، هم‌مسیر با آینده</p></div><img src="assets/fara-global-hero-no-logo.png" width="1672" height="941" alt="شبکه ارتباطات جهانی، بندر تجاری، صنایع نفت و گاز، ساختمان‌ها و منسوجات" fetchpriority="high" decoding="async"></div></section>
<section class="activities wrap" id="activities"><h2 class="activities-title">حوزه‌های فعالیت ما</h2><div class="activity-grid">${cards}</div></section>
<section class="about" id="about"><div class="wrap about-inner"><div><span class="eyebrow">درباره گروه بین‌المللی فرا</span><h2>فرصت‌ها را به هم <em>پیوند می‌دهیم.</em></h2></div><div><p>فرا با کنار هم قرار دادن تجارت، صنعت، لجستیک و سرمایه‌گذاری، زمینهٔ همکاری میان تولیدکنندگان، تأمین‌کنندگان، صاحبان پروژه و بازارهای هدف را دنبال می‌کند.</p><p>شناخت نیاز، بررسی ظرفیت‌ها و تعریف چارچوبی روشن برای همکاری، نقطهٔ آغاز ماست؛ از تأمین تجهیزات و کالا تا توسعه طرح‌های تولیدی و سرمایه‌گذاری.</p><a class="text-link" href="investment.html">بیشتر درباره تجارت، صنعت و سرمایه‌گذاری <span aria-hidden="true">←</span></a></div></div></section>
<section class="direction wrap" id="direction"><div class="section-heading direction-heading"><h2>نگاه بلندمدت. گام‌های روشن.</h2><p>افقی که دنبال می‌کنیم، همکاری پایدار بر پایهٔ هدف مشترک و تعهد به مسیر است.</p></div><div class="direction-grid"><article><span class="direction-symbol" aria-hidden="true">◎</span><span class="eyebrow">۰۱ / چشم‌انداز</span><h3>همراهی قابل اتکا در مسیر توسعه</h3><p>تبدیل شدن به مجموعه‌ای قابل اتکا در پیوند تجارت، صنعت و سرمایه‌گذاری؛ با نگاهی فراتر از مرزها و تمرکز بر خلق ارزش پایدار.</p></article><article><span class="direction-symbol" aria-hidden="true">↗</span><span class="eyebrow">۰۲ / مأموریت</span><h3>پیوند نیازها با ظرفیت‌ها</h3><p>شناسایی فرصت‌های همکاری، تسهیل تأمین و تجارت و همراهی در توسعه طرح‌های صنعتی و سرمایه‌گذاری، متناسب با نیاز هر حوزه.</p></article><article><span class="direction-symbol" aria-hidden="true">◇</span><span class="eyebrow">۰۳ / ارزش‌های ما</span><h3>شفافیت، تعهد و نگاه پایدار</h3><p>شفافیت در تعریف همکاری، مسئولیت‌پذیری در تعهدات، احترام به منافع مشترک و توجه به بهره‌وری منابع و توسعه بلندمدت.</p></article></div></section>
</main>
<footer class="home-footer"><div class="wrap footer-main"><div class="footer-brand">${logo}<p>فراتر از مرزها، هم‌مسیر با آینده.</p><span>تجارت، صنعت و سرمایه‌گذاری</span></div><div><h2>دسترسی سریع</h2><a href="#activities">حوزه‌های فعالیت</a><a href="#about">درباره فرا</a><a href="#direction">چشم‌انداز و مأموریت</a><a href="investment.html#cooperation">مسیر همکاری</a></div><div class="footer-fields"><h2>حوزه‌های فعالیت</h2>${fields.map(f=>`<a href="${f[3]}">${f[1]}</a>`).join('')}</div></div><div class="wrap footer-bottom"><span>تمامی حقوق این وب‌سایت متعلق به گروه بین‌المللی فرا است.</span><a href="#main">بازگشت به بالا ↑</a></div></footer><script src="navigation.js" defer></script></body></html>\n`;
fs.writeFileSync(path.join(__dirname,'dist/index.html'),html);
}
module.exports={buildEntrance};
if(require.main===module)buildEntrance();















