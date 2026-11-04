const fs=require('node:fs');
const path=require('node:path');
const ROOT=__dirname;
const titles={home:'صفحهٔ اصلی',mining:'تجهیزات صنایع معدنی',logistics:'حمل‌ونقل و لجستیک',trade:'صادرات و واردات',energy:'نفت، گاز و پتروشیمی',textiles:'نساجی و منسوجات','real-estate':'عمران و سرمایه‌گذاری ملکی',agriculture:'کشاورزی و کشت و صنعت'};
const pages=Object.entries(titles).map(([id,title])=>({id,title,href:id==='home'?'index.html':`sectors/${id}.html`}));
for(const page of pages)if(!fs.existsSync(path.join(ROOT,'dist',page.href)))throw Error('Missing page '+page.href);
fs.writeFileSync(path.join(ROOT,'dist/fara-admin/site.json'),JSON.stringify({pages,menu:JSON.parse(fs.readFileSync(path.join(ROOT,'content/navigation.json'),'utf8'))},null,2)+'\n');
console.log('Online admin page and menu configuration updated; installation identity preserved.');
