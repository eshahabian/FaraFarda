const fs = require('node:fs');

function buildEntrance() {
  require('./build-investment.cjs').buildInvestment();
  const cards = [
    ['mining', 'تجهیزات صنایع معدنی', 'sectors/mining.html', 3, 30.4],
    ['logistics', 'حمل‌ونقل و لجستیک', 'logistics.html', 50.6, 30.4],
    ['trade', 'صادرات و واردات', 'sectors/trade.html', 3, 46.7],
    ['energy', 'نفت، گاز و پتروشیمی', 'sectors/energy.html', 50.6, 46.7],
    ['textiles', 'نساجی و منسوجات', 'sectors/textiles.html', 3, 63.2],
    ['real-estate', 'عمران و سرمایه‌گذاری ملکی', 'sectors/real-estate.html', 50.6, 63.2],
    ['agriculture', 'کشاورزی و کشت و صنعت', 'sectors/agriculture.html', 3, 79.6],
    ['group', 'تجارت، صنعت و سرمایه‌گذاری', 'investment.html', 50.6, 79.6]
  ];
  const links = cards.map(([id, label, href, left, top]) => `<a class="sector-entry" data-sector="${id}" href="${href}" style="left:${left}%;top:${top}%" aria-label="${label}"><span>${label}</span></a>`).join('\n');
  fs.writeFileSync('dist/index.html', `<!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#001329"><meta name="description" content="گروه بین‌المللی فرا؛ فراتر از مرزها، هم‌مسیر با آینده. حوزه فعالیت خود را انتخاب کنید."><title>گروه بین‌المللی فرا | حوزه‌های فعالیت</title><link rel="icon" href="favicon.ico"><link rel="stylesheet" href="entrance.css"></head><body><main class="group-entrance"><h1 class="sr-only">گروه بین‌المللی فرا؛ حوزه‌های فعالیت</h1><img class="entrance-art" src="assets/group-entrance.jpg" width="1024" height="1280" alt="گروه بین‌المللی فرا؛ فراتر از مرزها، هم‌مسیر با آینده" fetchpriority="high"><nav aria-label="انتخاب حوزه فعالیت">${links}</nav></main></body></html>\n`);
}
module.exports = { buildEntrance };
if (require.main === module) buildEntrance();
