
document.addEventListener("DOMContentLoaded", function(){
  const containers = document.querySelectorAll('[data-stars]');
  containers.forEach(c => {
    const input = c.querySelector('input[name="rating"]');
    const stars = c.querySelectorAll('.star');
    const update = (val)=>{
      stars.forEach((s,i)=>{ if(i < val) s.classList.add('selected'); else s.classList.remove('selected'); });
    };
    stars.forEach((s,i)=>{ s.addEventListener('click',()=>{ input.value = (i+1); update(i+1); }); });
    update(parseInt(input.value||0));
  });
});
