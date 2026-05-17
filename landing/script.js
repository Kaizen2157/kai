  // Hamburger menu
  const ham = document.getElementById('hamburger');
  const nav = document.getElementById('nav-links');
  ham.addEventListener('click', () => {
    nav.classList.toggle('open');
  });

  // Close mobile nav on link click
  nav.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => nav.classList.remove('open'));
  });

  // Scroll reveal
  const revealEls = document.querySelectorAll('.reveal');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => entry.target.classList.add('visible'), i * 60);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  revealEls.forEach(el => observer.observe(el));

  // Navbar scroll effect
  const navbar = document.getElementById('navbar');
  window.addEventListener('scroll', () => {
    navbar.style.background = window.scrollY > 60
      ? 'rgba(3,0,69,0.95)'
      : 'rgba(3,0,69,0.72)';
  });


  function togglePw(id,btn){
  const inp=document.getElementById(id);
  const hide=inp.type==='password';
  inp.type=hide?'text':'password';
  btn.querySelector('svg').innerHTML=hide
    ?'<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>'
    :'<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>';
}
function strength(inp){
  const v=inp.value,segs=['ss1','ss2','ss3','ss4'].map(id=>document.getElementById(id)),cls=['w','f','g','s'];
  let sc=0;
  if(v.length>=6)sc++;
  if(v.length>=10)sc++;
  if(/[A-Z]/.test(v)&&/[0-9]/.test(v))sc++;
  if(/[^A-Za-z0-9]/.test(v))sc++;
  segs.forEach((s,i)=>{s.className='str-seg';if(i<sc)s.classList.add(cls[sc-1])});
}