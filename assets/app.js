// behappier — assets/app.js (MVP)
(function(){
  // Smooth page transition when clicking duration buttons on home
  document.addEventListener('click', function(e){
    const a = e.target.closest('a.btn.duration');
    if (!a) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || a.target === '_blank') return; // do not hijack new tab
    e.preventDefault();
    const href = a.getAttribute('href');
    if (!href) return (window.location = a.href);
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches){
      window.location = href; return;
    }
    document.body.classList.add('leaving');
    setTimeout(()=>{ window.location = href; }, 280);
  }, {capture:true});

  const $ = (sel, el=document) => el.querySelector(sel);
  const timer = $('#timer');
  if (!timer) return;

  const mins = parseInt(timer.getAttribute('data-mins') || '1', 10);
  let total = mins * 60; // seconds
  let remaining = total;
  let running = false;
  let rafId = null;
  let lastTick = null;

  const displayEl = timer.firstElementChild; // where we render mm:ss
  const btnStart = $('[data-action="start"]', timer);
  const btnPause = $('[data-action="pause"]', timer);
  const btnReset = $('[data-action="reset"]', timer);
  const btnFinish = $('[data-action="finish"]', timer);
  const postForm = $('#post-timer');

  // End-of-timer sound
  const endSound = new Audio('assets/sfx/timer-end.mp3');
  endSound.preload = 'auto';
  let soundPrimed = false;
  function primeSound(){
    if (soundPrimed) return;
    try {
      endSound.muted = true;
      const p = endSound.play();
      if (p && p.then){
        p.then(()=>{ endSound.pause(); endSound.currentTime = 0; endSound.muted = false; soundPrimed = true; }).catch(()=>{});
      } else {
        endSound.pause(); endSound.currentTime = 0; endSound.muted = false; soundPrimed = true;
      }
    } catch (e) { /* ignore */ }
  }

  function fmt(s){
    const m = Math.floor(s/60).toString().padStart(2,'0');
    const ss = Math.floor(s%60).toString().padStart(2,'0');
    return `${m}:${ss}`;
  }

  function render(){
    if (displayEl) displayEl.textContent = fmt(Math.max(0, remaining));
  }

  function tick(ts){
    if (!running){ return; }
    if (lastTick == null) lastTick = ts;
    const delta = (ts - lastTick) / 1000; // to seconds
    lastTick = ts;
    remaining -= delta;
    if (remaining <= 0){
      remaining = 0;
      running = false;
      finish();
      return;
    }
    render();
    rafId = requestAnimationFrame(tick);
  }

  function start(){
    if (running) return;
    // Prime audio on first explicit user start to avoid autoplay blocks
    primeSound();
    running = true; lastTick = null;
    rafId = requestAnimationFrame(tick);
  }

  function pause(){
    running = false; lastTick = null;
    if (rafId) cancelAnimationFrame(rafId);
  }

  function reset(){
    pause(); remaining = total; render();
    if (postForm) postForm.style.display = 'none';
    timer.style.display = '';
  }

  function finishNow(){
    // Manually finish: set to 0, prime audio, then finish flow
    remaining = 0; render();
    primeSound();
    finish();
  }

  function finish(){
    pause(); render();
    // play end sound (best-effort)
    try { endSound.currentTime = 0; endSound.play().catch(()=>{}); } catch (e) {}
    // reveal post-form
    if (postForm){ postForm.style.display = ''; }
    timer.style.display = 'none';
    // try to focus first input
    const mood = postForm && postForm.querySelector('input[name="mood"]');
    if (mood) mood.focus();
  }

  btnStart && btnStart.addEventListener('click', start);
  btnPause && btnPause.addEventListener('click', pause);
  btnReset && btnReset.addEventListener('click', reset);
  btnFinish && btnFinish.addEventListener('click', finishNow);

  // initial render
  render();
})();
