document.querySelectorAll('[data-hero-slider]').forEach(slider => {
    const slides = [...slider.querySelectorAll('[data-slide]')];
    if (slides.length < 2) return;
    const controls = slider.querySelector('[data-slider-controls]');
    const dots = [...slider.querySelectorAll('[data-dot]')];
    const pause = slider.querySelector('[data-pause]');
    const motion = matchMedia('(prefers-reduced-motion: reduce)');
    let current = 0, timer, stopped = motion.matches, hovered = false;
    controls.hidden = false;
    function show(index) {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            slide.classList.toggle('is-current', i === current);
            slide.inert = i !== current;
            slide.setAttribute('aria-hidden', String(i !== current));
        });
        dots.forEach((dot, i) => dot.setAttribute('aria-pressed', String(i === current)));
    }
    function schedule() {
        clearInterval(timer);
        pause.textContent = stopped ? '▶' : 'Ⅱ';
        pause.setAttribute('aria-label', stopped ? 'স্বয়ংক্রিয় স্লাইড চালু করুন' : 'স্বয়ংক্রিয় স্লাইড বন্ধ করুন');
        if (!stopped && !hovered && !document.hidden && !slider.contains(document.activeElement)) {
            timer = setInterval(() => show(current + 1), 6000);
        }
    }
    function manual(index) { stopped = true; show(index); schedule(); }
    slider.querySelector('[data-prev]').addEventListener('click', () => manual(current - 1));
    slider.querySelector('[data-next]').addEventListener('click', () => manual(current + 1));
    dots.forEach((dot, i) => dot.addEventListener('click', () => manual(i)));
    pause.addEventListener('click', () => { stopped = !stopped; schedule(); });
    slider.addEventListener('mouseenter', () => { hovered = true; schedule(); });
    slider.addEventListener('mouseleave', () => { hovered = false; schedule(); });
    slider.addEventListener('focusin', schedule);
    slider.addEventListener('focusout', () => setTimeout(schedule, 0));
    controls.addEventListener('keydown', event => {
        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
            event.preventDefault();
            manual(current + (event.key === 'ArrowRight' ? 1 : -1));
            dots[current].focus();
        }
    });
    document.addEventListener('visibilitychange', schedule);
    motion.addEventListener('change', () => { stopped = motion.matches; schedule(); });
    schedule();
});
