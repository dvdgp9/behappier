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
    const btnToggle = $('[data-action="toggle"]', timer);
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
      primeSound();
      running = true; lastTick = null;
      rafId = requestAnimationFrame(tick);
      // UI state: toggle becomes "Pausar"
      updateToggleUI('pause');
      // Enable reset when timer has begun
      if (btnReset) btnReset.disabled = false;
    }

    function pause(){
      if (!running) return;
      running = false; lastTick = null;
      if (rafId) cancelAnimationFrame(rafId);
      // UI state: toggle becomes "Reanudar"
      updateToggleUI('resume');
    }

    function reset(){
      pause(); remaining = total; render();
      if (postForm) postForm.style.display = 'none';
      timer.style.display = '';
      // UI state: toggle back to "Empezar", disable reset again
      updateToggleUI('start');
      if (btnReset) btnReset.disabled = true;
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
      
      // Show daily mood modal instead of old post-form
      const moodModal = document.getElementById('daily-mood-modal');
      if (moodModal) {
        moodModal.style.display = 'flex';
        // Focus first mood option
        const firstMood = moodModal.querySelector('input[name="daily_mood"]');
        if (firstMood) firstMood.focus();
      }
      timer.style.display = 'none';

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

    function updateToggleUI(state){
      if (!btnToggle) return;
      const labelSpan = btnToggle.querySelector('.btn-label');
      const icon = btnToggle.querySelector('i');
      btnToggle.classList.remove('is-active');
      if (state === 'pause'){
        // running
        if (labelSpan) labelSpan.textContent = 'Pausar';
        if (icon) icon.className = 'iconoir-pause';
        btnToggle.setAttribute('aria-label', 'Pausar');
        btnToggle.classList.add('is-active');
      } else if (state === 'resume'){
        if (labelSpan) labelSpan.textContent = 'Reanudar';
        if (icon) icon.className = 'iconoir-play';
        btnToggle.setAttribute('aria-label', 'Reanudar');
      } else {
        if (labelSpan) labelSpan.textContent = 'Empezar';
        if (icon) icon.className = 'iconoir-play';
        btnToggle.setAttribute('aria-label', 'Empezar');
      }
    }

    btnToggle && btnToggle.addEventListener('click', function(){
      if (running) { pause(); } else { start(); }
    });
    btnReset && btnReset.addEventListener('click', reset);
    btnFinish && btnFinish.addEventListener('click', finishNow);

    // initial render and UI state
    render();
    updateToggleUI('start');
    if (btnReset) btnReset.disabled = true;
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

  // Daily mood modal handlers
  function initDailyMoodModal(){
    const modal = document.getElementById('daily-mood-modal');
    if (!modal) return;

    const saveBtn = document.getElementById('save-daily-mood');
    const keepBtn = document.getElementById('keep-mood');
    const skipBtn = document.getElementById('skip-mood');
    const postForm = document.getElementById('post-timer');

    function closeMoodModal(){
      // Close modal and return to home after finishing the flow
      try { modal.style.display = 'none'; } catch(_) {}
      window.location.href = 'home.php';
    }

    function saveDailyMood(){
      const selectedMood = modal.querySelector('input[name="daily_mood"]:checked');
      const noteInput = modal.querySelector('input[name="daily_note"]');
      
      if (!selectedMood) {
        alert('Por favor selecciona cómo te sientes');
        return;
      }

      const csrf = document.querySelector('input[name="csrf"]').value;
      const params = new URLSearchParams();
      params.set('action', 'daily_mood');
      params.set('csrf', csrf);
      params.set('mood', selectedMood.value);
      params.set('note', noteInput ? noteInput.value : '');

      fetch('task.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString(),
        credentials: 'same-origin'
      }).then(r=>r.json()).then(json=>{
        if (json && json.ok) {
          closeMoodModal();
        } else {
          alert('Error al guardar. Inténtalo de nuevo.');
        }
      }).catch(()=>{
        alert('Error de conexión. Inténtalo de nuevo.');
      });
    }

    // Event listeners
    if (saveBtn) saveBtn.addEventListener('click', saveDailyMood);
    if (keepBtn) keepBtn.addEventListener('click', closeMoodModal);
    if (skipBtn) skipBtn.addEventListener('click', closeMoodModal);

    // Close modal on overlay click
    modal.addEventListener('click', function(e){
      if (e.target === modal) closeMoodModal();
    });

    // Handle mood option selection visual feedback
    const moodOptions = modal.querySelectorAll('.mood-option');
    const moodInputs = modal.querySelectorAll('input[name="daily_mood"]');
    moodOptions.forEach(option => {
      option.addEventListener('click', function(){
        moodOptions.forEach(opt => opt.classList.remove('selected'));
        this.classList.add('selected');
      });
    });
    // Keep visual state in sync when radios change (keyboard navigation, etc.)
    moodInputs.forEach(input => {
      input.addEventListener('change', function(){
        moodOptions.forEach(opt => opt.classList.remove('selected'));
        const label = this.closest('.mood-option');
        if (label) label.classList.add('selected');
      });
    });
  }

  // Initialize mood modal
  initDailyMoodModal();

  // Register service worker for PWA
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function(){
      navigator.serviceWorker.register('/sw.js').catch(()=>{});
    });
  }
})();
