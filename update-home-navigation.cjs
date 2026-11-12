const fs = require('fs');
const { renderHeader } = require('./navigation.cjs');
const { renderFooter } = require('./footer.cjs');
const { applyPageEdits } = require('./site-text.cjs');
const file = 'dist/logistics.html';
let home = fs.readFileSync('content/home.html', 'utf8');
home = home.replace(/<header\b[\s\S]*?<\/header>/, renderHeader('', 'logistics-home'));
home = home.replace(/<footer\b[\s\S]*?<\/footer>/, renderFooter());
if (!home.includes('href="footer.css')) home = home.replace('</head>', '<link rel="stylesheet" href="footer.css?v=1"></head>');
home = home.replaceAll('#catalogue', '#profile').replaceAll('مشاهده خدمات و کاتالوگ', 'معرفی و شرح خدمات').replaceAll('مطالعهٔ کاتالوگ لجستیک', 'مطالعهٔ خدمات لجستیک').replaceAll('معرفی و کاتالوگ PDF', 'معرفی و شرح فعالیت‌ها').replaceAll('فایل‌های مربوط به آن', 'توضیحات کامل آن');
if (!home.includes('href="navigation.css')) home = home.replace('</head>', '<link rel="stylesheet" href="navigation.css?v=1"></head>');
if (!home.includes('src="navigation.js')) home = home.replace('</body>', '<script src="navigation.js" defer></script></body>');
fs.writeFileSync(file, applyPageEdits(home, 'home'));
require('./build-entrance.cjs').buildEntrance();
console.log('Updated logistics page and shared navigation.');

if (fs.existsSync(__dirname + '/prepare-online-admin.cjs')) require('./prepare-online-admin.cjs');
