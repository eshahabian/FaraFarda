const fs=require('fs');
const dir='content/';
const p=text=>({type:'paragraph',text}),h=text=>({type:'heading',text}),s=text=>({type:'subheading',text}),ul=items=>({type:'list',items});
const read=slug=>JSON.parse(fs.readFileSync(dir+slug+'.json','utf8'));
const save=(slug,data)=>fs.writeFileSync(dir+slug+'.json',JSON.stringify(data,null,2)+'\n');
function section(data,start,end,blocks){const a=data.blocks.findIndex(b=>b.type==='heading'&&b.text.startsWith(start));const b=end?data.blocks.findIndex((b,i)=>i>a&&b.type==='heading'&&b.text.startsWith(end)):data.blocks.length;if(a<0||b<0)throw Error('Missing chapter '+start);data.blocks.splice(a,b-a,...blocks);}
let data=read('mining');
section(data,'حوزه‌های اصلی','استخراج تا',[h('حوزه‌های اصلی فعالیت'),ul(['ماشین‌آلات استخراج و حفاری معادن','تجهیزات خردایش و سنگ‌شکنی','تجهیزات سرند، دانه‌بندی و طبقه‌بندی','سیستم‌های انتقال و جابه‌جایی مواد','تجهیزات شست‌وشو، آبگیری و فرآوری مواد معدنی','خطوط کامل خردایش، دانه‌بندی و آماده‌سازی مواد','هماهنگی طراحی، ساخت و تأمین تجهیزات سفارشی','توسعه همکاری‌های فنی و تجاری در بازارهای بین‌المللی'])]);
for(let i=0;i<data.blocks.length;i++){
 const b=data.blocks[i];if(!b.text)continue;
 b.text=b.text.replace('عمق موردنیاز: روش حفاری','عمق موردنیاز، روش حفاری');
 if(b.text.endsWith(' تجهیزات شست‌وشو و آبگیری')){b.text=b.text.replace(/ تجهیزات شست‌وشو و آبگیری$/,'');data.blocks.splice(i+1,0,h('۶. تجهیزات شست‌وشو و آبگیری'));}
 if(b.text.startsWith('۸ خطوط کامل'))data.blocks.splice(i+1,0,p('امکان هماهنگی برای تأمین تجهیزات و توسعه خطوط صنعتی یکپارچه، متناسب با نیاز پروژه، وجود دارد.'));
 if(b.text==='سرندهای آبگیری برای کاهش رطوبت مواد شسته‌شده و جداسازی بخشی از آب همراه محصول به کار می‌روند')b.text+='.';
}
save('mining',data);

data=read('logistics');
section(data,'۲ حوزه','۳ انواع',[
 h('۲. حوزه‌های تخصصی فعالیت'),
 s('الف) حمل‌ونقل دریایی | Maritime Freight'),
 p('حمل‌ونقل دریایی یکی از اصلی‌ترین محورهای فعالیت FARA TransLog است. این شیوه با توجه به ظرفیت بالای جابه‌جایی و صرفهٔ اقتصادی، راهکاری مهم برای تجارت بین‌المللی و انتقال محموله‌های حجیم محسوب می‌شود.'),
 ul(['حمل کانتینری کامل (FCL)','حمل خرده‌بار کانتینری (LCL)','حمل محموله‌های فله خشک (Dry Bulk)','حمل محموله‌های فله مایع (Liquid Bulk)','حمل کالاهای عمومی (General Cargo)','حمل محموله‌های سنگین و فوق‌سنگین','حمل کالاهای پروژه‌ای و صنعتی','هماهنگی عملیات بارگیری، تخلیه و حمل بندری','برنامه‌ریزی مسیرهای دریایی و انتخاب خطوط کشتیرانی متناسب با محموله']),
 s('ب) حمل‌ونقل هوایی | Air Freight'),
 p('حمل‌ونقل هوایی برای محموله‌هایی که سرعت تحویل، امنیت و زمان‌بندی در آن‌ها اهمیت ویژه‌ای دارد، از جایگاه مهمی برخوردار است.'),
 ul(['حمل هوایی صادراتی و وارداتی','حمل بارهای تجاری و صنعتی','حمل قطعات و تجهیزات حساس','حمل محموله‌های فوری و زمان‌محور','خدمات حمل هوایی عمومی و تخصصی','هماهنگی امور فرودگاهی و اسناد حمل','مدیریت عملیات دریافت و تحویل محموله']),
 s('ج) حمل‌ونقل زمینی و جاده‌ای | Road Freight'),
 p('حمل‌ونقل جاده‌ای یکی از انعطاف‌پذیرترین روش‌های انتقال کالا در تجارت منطقه‌ای و بین‌المللی است. این بخش با تکیه بر ظرفیت ناوگان حمل‌ونقل جاده‌ای و برنامه‌ریزی مسیرهای بین‌المللی، امکان جابه‌جایی کالا میان مراکز تولیدی، انبارها، پایانه‌های مرزی و مقاصد تجاری را فراهم می‌کند.'),
 ul(['حمل جاده‌ای داخلی و بین‌المللی','حمل ترانزیتی و عبوری (Transit)','حمل با کامیون‌های چادری و ترانزیت','حمل کانتینری جاده‌ای','حمل مواد معدنی و محصولات صنعتی','حمل محموله‌های فله و بسته‌بندی‌شده','حمل محموله‌های سنگین و پروژه‌ای','حمل تانکری و محموله‌های مایع با تجهیزات مناسب','حمل ترکیبی جاده‌ای و دریایی','برنامه‌ریزی مسیر و هماهنگی امور مرزی']),
 s('د) حمل‌ونقل ریلی | Rail Freight'),
 p('حمل‌ونقل ریلی راهکاری مؤثر برای انتقال محموله‌های حجیم و سنگین در مسیرهای داخلی و بین‌المللی است. این روش در بسیاری از مسیرها می‌تواند با کاهش هزینه‌های عملیاتی و افزایش ظرفیت جابه‌جایی، نقش مهمی در بهینه‌سازی زنجیرهٔ تأمین ایفا کند.'),
 ul(['حمل ریلی کانتینری','حمل مواد معدنی و محصولات فله','حمل کالاهای صنعتی و تجاری','حمل محموله‌های سنگین','هماهنگی حمل ترکیبی ریلی و جاده‌ای','برنامه‌ریزی مسیرهای ریلی بین‌المللی','هماهنگی عملیات بارگیری و تخلیه'])
]);
section(data,'۳ انواع','۴ خدمات',[
 h('۳. انواع روش‌های حمل دریایی'),
 s('حمل کانتینری | Container Shipping'),p('انتقال کالا در کانتینرهای استاندارد ۲۰ و ۴۰ فوت، به‌صورت FCL و LCL برای انواع محموله‌های تجاری و صنعتی.'),
 s('حمل فله خشک | Dry Bulk Shipping'),p('جابه‌جایی مواد معدنی، سنگ‌آهن، کرومیت، سیمان، کلینکر، غلات و سایر کالاهای فله خشک.'),
 s('حمل فله مایع | Liquid Bulk Shipping'),p('انتقال فرآورده‌های نفتی، محصولات پتروشیمی، مواد شیمیایی مجاز و سایر محموله‌های مایع با شناورهای تخصصی.'),
 s('حمل پروژه‌ای و محموله‌های خاص | Project Cargo'),p('انتقال تجهیزات صنعتی، ماشین‌آلات معدنی، قطعات حجیم، محموله‌های سنگین و محموله‌های خارج از ابعاد استاندارد.'),
 s('حمل کالای عمومی | General Cargo'),p('انتقال انواع کالاهای بسته‌بندی‌شده، پالت‌شده، فلزی، صنعتی و محموله‌هایی که نیازمند شیوهٔ بارگیری و تخلیهٔ خاص هستند.')
]);
for(const b of data.blocks){if(!b.text)continue;
 if(b.text.includes('الزامات تجاری مشتریان')&&!b.text.endsWith('است.'))b.text+=' است.';
 if(b.text.includes('ترکیب حمل دریایی')&&b.text.endsWith('بهبود عملکرد'))b.text+=' زنجیرهٔ تأمین باشد.';
 if(b.text==='صنایع و کالاهای تحت پوشش'){b.type='heading';b.text='۶. صنایع و کالاهای تحت پوشش';}
 if(b.text.startsWith('چشم‌انداز FARA TransLog چشم‌انداز'))b.text=b.text.replace('چشم‌انداز FARA TransLog ','');
 if(b.text.startsWith('.٩'))b.text=b.text.replace('.٩','۹.');
 if(b.text.startsWith('FARA TransLog؛')||b.text.startsWith('FARA TransLog |'))b.type='paragraph';
}
save('logistics',data);

data=read('energy');
const products=['پلی‌اتیلن (PE)','پلی‌پروپیلن (PP)','پلی‌وینیل کلراید (PVC)','پلی‌استایرن (PS)','متانول (Methanol)','اوره (Urea)','گوگرد (Sulfur)','انواع حلال‌های صنعتی','مواد شیمیایی پایه و واسطه','خوراک و مواد اولیهٔ صنایع پایین‌دستی'];
data.blocks=data.blocks.flatMap(b=>{
 if(b.type==='paragraph'&&b.text.startsWith('پلی‌اتیلن'))return [ul(products)];
 if(b.type==='paragraph'&&b.text.startsWith('فعالیت در حوزه‌های متنوع'))return [ul(['شبکهٔ ارتباطی تجاری و تأمین بین‌المللی','فعالیت در حوزه‌های متنوع نفت، گاز و پتروشیمی','امکان هماهنگی میان تأمین، تجارت و لجستیک','شناخت ظرفیت‌های بازارهای منطقه‌ای و بین‌المللی','ارائهٔ راهکارهای متناسب با نیاز مشتریان صنعتی','پیگیری یکپارچهٔ فرآیندهای بازرگانی و تأمین','رویکرد مبتنی بر همکاری پایدار و منافع مشترک'])];
 if(b.text?.startsWith('شبکه ارتباطی تجاری و تأمین بین‌المللی'))return [];
 if(b.text){b.text=b.text.replace('گازوتیل','گازوئیل').replace('تولیدکنندگان:','تولیدکنندگان،');
 if(/^۳-[۱-۶]/.test(b.text))b.type='subheading';
 if(/^[1-7][. ]/.test(b.text)){b.type='paragraph';b.text=b.text.replace(/^[1-7][. ]+/, '');}
 if(b.text==='منشور فعالیت و اصول حرفه‌ای'){b.type='heading';b.text='۶. منشور فعالیت و اصول حرفه‌ای';}
 if(b.text.startsWith('FARA؛'))b.type='paragraph';
 }
 return [b];
});
const petroleum=data.blocks.findIndex(b=>b.type==='subheading'&&b.text.startsWith('۳-۴'));
const chemicals=data.blocks.findIndex((b,i)=>i>petroleum&&b.type==='subheading'&&b.text.startsWith('۳-۵'));
data.blocks.splice(petroleum+2,chemicals-petroleum-2,ul(['گازوئیل (Gasoil / Diesel)','نفت کوره (Fuel Oil)','نفتا (Naphtha)','گاز مایع (LPG)','قیر (Bitumen)','گوگرد (Sulfur)','سایر فرآورده‌های پالایشی و هیدروکربنی']),p('معاملات بر اساس مشخصات فنی، استانداردهای کیفی مورد توافق و الزامات قانونی انجام می‌شوند.'));
save('energy',data);

data=read('trade');
for(const b of data.blocks){if(!b.text)continue;
 if(b.text.startsWith('توسعه بازارهای بین‌المللی و شناسایی')){b.type='heading';b.text='۶. توسعه بازارهای بین‌المللی و شناسایی فرصت‌های تجاری';}
 b.text=b.text.replace('فرصت‌هاء','فرصت‌ها،').replace('مبدا‌تأمین','مبدأ تأمین');
}
save('trade',data);

data=read('agriculture');
for(const b of data.blocks){if(!b.text)continue;
 if(b.text.includes('کشاورزی کشور به فرصت‌هایی'))b.text=b.text.replace('زنجیره ارزن','زنجیره ارزش');
 if(b.text.includes('FARA Agriculture & Agro-Industry')&&!b.text.startsWith('بر همین اساس'))b.type='paragraph';
}
save('agriculture',data);
console.log('Source-checked transcription corrections and lists applied.');
