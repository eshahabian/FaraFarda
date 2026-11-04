const fs=require('node:fs');
const path=require('node:path');
const decode=t=>t.replace(/&(#x[\da-f]+|#\d+|amp|lt|gt|quot|apos|nbsp);/gi,(m,k)=>{if(k[0]==='#'){const n=k[1].toLowerCase()==='x'?parseInt(k.slice(2),16):Number(k.slice(1));return n>0&&n<=0x10ffff?String.fromCodePoint(n):m}return ({amp:'&',lt:'<',gt:'>',quot:'"',apos:"'",nbsp:' '})[k.toLowerCase()]||m});
const escape=t=>t.replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');
function scan(html,page){
 const fields=[],tokens=[];
 const stack=[{tag:'root',path:'',counts:{},textCount:0}];
 const regex=/<!--[\s\S]*?-->|<![^>]*>|<\/?[A-Za-z][A-Za-z0-9:-]*(?:"[^"]*"|'[^']*'|[^'">])*>|[^<]+|</g;
 for(const m of html.matchAll(regex)){
  const raw=m[0];
  if(['script','style'].includes(stack.at(-1).tag)&&!raw.toLowerCase().startsWith('</'+stack.at(-1).tag))continue;
  if(raw.startsWith('<!--')||raw.startsWith('<!'))continue;
  if(raw[0]==='<'){
   const match=raw.match(/^<(\/?)([\w:-]+)/);if(!match)continue;
   const tag=match[2].toLowerCase();
   if(match[1]){const at=stack.findLastIndex(n=>n.tag===tag);if(at>0){stack[at].closeStart=m.index;stack.length=at}continue}
   const parent=stack[stack.length-1];const nth=parent.counts[tag]||0;parent.counts[tag]=nth+1;
   const node={tag,path:parent.path+'/'+tag+'['+nth+']',counts:{},textCount:0,start:m.index,end:m.index+raw.length,raw};
   tokens.push(node);
   if(!/^(area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)$/.test(tag)&&!raw.endsWith('/>'))stack.push(node);
   continue;
  }
  const parent=stack[stack.length-1];const nth=parent.textCount++;
  if(stack.some(n=>['head','script','style','noscript','svg','button'].includes(n.tag)))continue;
  const value=decode(raw).trim();if(!/[\p{L}\p{N}]/u.test(value))continue;
  if(stack.some(n=>/aria-hidden\s*=\s*["']true/.test(n.raw||'')))continue;
  const shared=stack.find(n=>n.tag==='header'||n.tag==='footer');
  const scope=shared?shared.tag:'page';
  const menu=stack.some(n=>/class=["'][^"']*fara-sector-strip/.test(n.raw||''));
  const derived=(stack.some(n=>/class=["'][^"']*profile-contents/.test(n.raw||''))&&parent.tag==='a'&&/href=["']#profile-/.test(parent.raw))||menu;
  const relative=shared?parent.path.slice(shared.path.length):parent.path;
  const key=(shared?'shared:'+scope:page)+relative+'/text['+nth+']';
  const label=({h1:'عنوان اصلی',h2:'عنوان بخش',h3:'عنوان فرعی',p:'متن',li:'گزینهٔ فهرست',a:'متن لینک',small:'متن کوتاه',strong:'متن برجسته'})[parent.tag]||'متن';
  fields.push({id:key,scope,label,value,derived,menu,start:m.index,end:m.index+raw.length,raw,parentStart:parent.start,parentEnd:parent.end});
 }
 return {fields,tokens};
}
function readEdits(root=__dirname){try{return JSON.parse(fs.readFileSync(path.join(root,'content/site-edits.json'),'utf8'))}catch(e){if(e.code==='ENOENT')return {version:1,values:{}};throw e}}
function patch(html,replacements){for(const r of replacements.sort((a,b)=>b.start-a.start))html=html.slice(0,r.start)+r.value+html.slice(r.end);return html}
function applyPageEdits(html,page,edits=readEdits()){
 const {fields,tokens}=scan(html,page);const replacements=[],derived=new Map(),multiline=new Set();
 for(const f of fields){if(f.derived)continue;const edit=edits.values[f.id];if(!edit)continue;if(f.value!==edit.original&&f.value!==edit.value)continue;
  const prefix=f.raw.match(/^\s*/)[0],suffix=f.raw.match(/\s*$/)[0];
  replacements.push({start:f.start,end:f.end,value:prefix+escape(edit.value)+suffix});
  if(edit.value.includes('\n'))multiline.add(f.parentStart);
  if(f.scope==='page')derived.set(edit.original,edit.value);
 }
 for(const f of fields)if(f.derived&&!f.menu&&derived.has(f.value))replacements.push({start:f.start,end:f.end,value:escape(derived.get(f.value))});
 for(const node of tokens)if(multiline.has(node.start)){
  let raw=node.raw;
  if(/\sstyle=["']/.test(raw))raw=raw.replace(/style=(["'])(.*?)\1/,(_,q,style)=>'style='+q+style+';white-space:pre-line'+q);
  else raw=raw.replace(/>$/,' style="white-space:pre-line">');
  replacements.push({start:node.start,end:node.end,value:raw});
 }
 return patch(html,replacements);
}
module.exports={scan,readEdits,applyPageEdits,escape,patch};
