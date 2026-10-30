const fs = require('fs');
const path = require('path');
const escape = text => String(text).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');

function renderProfile(slug) {
  const file = path.join(__dirname, 'content', slug + '.json');
  if (!fs.existsSync(file)) return '';
  const profile = JSON.parse(fs.readFileSync(file, 'utf8'));
  const sections = [];
  let section = { title: profile.title, blocks: [] };
  for (const block of profile.blocks) {
    if (block.type === 'heading' && section.blocks.length) {
      sections.push(section);
      section = { title: block.text, blocks: [] };
    } else if (block.type === 'heading') {
      section.title = block.text;
    } else {
      section.blocks.push(block);
    }
  }
  if (section.blocks.length) sections.push(section);
  return `<section class="profile-section" id="profile"><div class="profile-layout"><aside class="profile-contents"><span>در این صفحه می‌خوانید</span><h2>${escape(profile.title)}</h2><nav aria-label="فهرست مطالب این حوزه">${sections.map((s, i) => `<a href="#profile-${i+1}">${escape(s.title)}</a>`).join('')}</nav><a class="profile-back" href="../index.html#sectors">همهٔ حوزه‌های فعالیت ←</a></aside><article class="profile-article" aria-label="${escape(profile.title)}">${sections.map((s, i) => `<section id="profile-${i+1}" class="profile-chapter"><div class="profile-chapter-number" aria-hidden="true">${String(i+1).padStart(2,'0')}</div><h2>${escape(s.title)}</h2>${s.blocks.map(b => b.type === 'list' ? `<ul>${b.items.map(t=>`<li>${escape(t)}</li>`).join('')}</ul>` : b.type === 'subheading' ? `<h3>${escape(b.text)}</h3>` : `<p>${escape(b.text)}</p>`).join('')}</section>`).join('')}</article></div></section>`;
}
module.exports = { renderProfile };
