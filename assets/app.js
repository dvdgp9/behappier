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

  function initTimer(root=document){
    const timer = $('#timer', root);
    const postForm = $('#post-timer', root);
    if (!timer) return; // not on this page or not yet loaded

    const mins = parseInt(timer.getAttribute('data-mins') || String(window.BEH_DURATION || '1'), 10);
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

      // Autosave: crea entrada inmediata (sin mood/note) y guarda entry_id oculto
      try {
        if (postForm){
          const csrf = postForm.querySelector('input[name="csrf"]').value;
          const taskId = postForm.querySelector('input[name="task_id"]').value;
          const entryIdInput = postForm.querySelector('input[name="entry_id"]');
          const params = new URLSearchParams();
          params.set('action', 'autosave');
          params.set('csrf', csrf);
          params.set('task_id', taskId);
          params.set('duration', String(window.BEH_DURATION || mins));
          fetch('task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString(),
            credentials: 'same-origin'
          }).then(r=>r.json()).then(json=>{
            if (json && json.ok && entryIdInput){ entryIdInput.value = String(json.entry_id || ''); }
          }).catch(()=>{});
        }
      } catch(_) { /* ignore */ }
    }

    btnStart && btnStart.addEventListener('click', start);
    btnPause && btnPause.addEventListener('click', pause);
    btnReset && btnReset.addEventListener('click', reset);
    btnFinish && btnFinish.addEventListener('click', finishNow);

    // initial render
    render();
  }

  // Expose initializer in case it's needed elsewhere
  window.BEH_initTimer = initTimer;

  // Initialize on first load
  initTimer(document);

  // Swap exercise via AJAX
  document.addEventListener('click', function(e){
    const a = e.target.closest('a.js-swap-exercise');
    if (!a) return;
    e.preventDefault();
    if (a.dataset.loading === '1') return;
    a.dataset.loading = '1';
    const url = `task.php?d=${encodeURIComponent(String(window.BEH_DURATION || 1))}&ajax=1&swap=1&_=${Date.now()}`;
    fetch(url, { credentials: 'same-origin' })
      .then(r => r.text())
      .then(html => {
        const holder = document.getElementById('exercise-block');
        if (!holder) return;
        // Replace outer HTML with the new block
        holder.outerHTML = html;
        // Re-init timer on the new content
        initTimer(document);
      })
      .catch(()=>{})
      .finally(()=>{ delete a.dataset.loading; });
  });

  // Register service worker for PWA
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function(){
      navigator.serviceWorker.register('/sw.js').catch(()=>{});
    });
  }
})();
