// behappier — assets/app.js (MVP)
(function(){
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
  const postForm = $('#post-timer');

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

  function finish(){
    pause(); render();
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

  // initial render
  render();
})();
