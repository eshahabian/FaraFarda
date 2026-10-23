const fs=require('fs'),path=require('path');
const pages=fs.readdirSync('dist/sectors');
if(pages.length!==7)throw Error('Expected seven sector pages');
let links=0;
for(const f of ['dist/index.html',...pages.map(f=>'dist/sectors/'+f)]){
 const h=fs.readFileSync(f,'utf8');
 if(!h.includes('lang="fa"')||!h.includes('dir="rtl"'))throw Error('Missing Persian RTL: '+f);
 if((h.match(/<h1>/g)||[]).length!==1)throw Error('Heading: '+f);
 for(const m of h.matchAll(/(?:href|src)="([^"]+)"/g)){
  const u=m[1];if(u.startsWith('http')||u.startsWith('#'))continue;
  const p=u.split(/[?#]/)[0];
  const target=p.startsWith('/')?'dist'+(p==='/'?'/index.html':p):path.join(path.dirname(f),p);
  if(!fs.existsSync(target))throw Error('Missing '+target);links++;
 }
}
console.log(`PASS: 7 sector pages, Persian RTL, headings, ${links} links and assets`);
