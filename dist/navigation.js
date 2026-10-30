(() => {
  const header=document.querySelector('[data-navigation]');
  if(!header)return;
  const button=header.querySelector('.fara-menu-toggle');
  const nav=header.querySelector('#fara-primary');
  const media=window.matchMedia('(max-width: 1000px)');
  header.classList.add('nav-ready');
  const setOpen=open=>{
    header.toggleAttribute('data-menu-open',open);
    button.setAttribute('aria-expanded',String(open));
    button.querySelector('[data-menu-label]').textContent=open?'بستن':'منو';
  };
  button.addEventListener('click',()=>setOpen(!header.hasAttribute('data-menu-open')));
  nav.addEventListener('click',e=>{if(e.target.closest('a'))setOpen(false)});
  document.addEventListener('click',e=>{if(!header.contains(e.target))setOpen(false)});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&header.hasAttribute('data-menu-open')){setOpen(false);button.focus()}});
  media.addEventListener('change',()=>setOpen(false));
})();
