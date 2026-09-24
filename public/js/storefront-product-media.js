(() => {
    'use strict';
    const fallback = document.querySelector('[data-product-media-script]').dataset.fallback;
    function recover(img) {
        if (img.getAttribute('src') !== fallback) img.src = fallback;
    }
    // Capture image errors only for product media, including dynamically rendered bag rows.
    document.addEventListener('error', event => {
        if (event.target.matches?.('img.product-artwork, .product-image-stage img, .product-viewer img')) recover(event.target);
    }, true);
    document.querySelectorAll('img.product-artwork, .product-image-stage img').forEach(img => {
        if (img.complete && !img.naturalWidth) recover(img);
    });

    const dialog = document.createElement('dialog');
    dialog.className = 'product-viewer';
    dialog.setAttribute('aria-label', 'Product image viewer');
    dialog.innerHTML = `<div class="product-viewer-toolbar">
        <button type="button" data-action="previous" aria-label="Previous image">←</button>
        <button type="button" data-action="next" aria-label="Next image">→</button>
        <button type="button" data-action="out" aria-label="Zoom out">−</button>
        <button type="button" data-action="in" aria-label="Zoom in">+</button>
        <button type="button" data-action="reset">Reset</button>
        <button type="button" data-action="close" autofocus>Close ×</button>
    </div><p class="product-viewer-status" role="status" aria-live="polite"></p>
    <div class="product-viewer-viewport"><div class="product-viewer-canvas"><img alt="" draggable="false"></div></div>`;
    document.body.append(dialog);
    const viewport = dialog.querySelector('.product-viewer-viewport');
    const image = dialog.querySelector('img');
    let images = [], index = 0, zoom = 1, label = '', previousFocus, previousOverflow;
    const control = action => dialog.querySelector(`[data-action="${action}"]`);
    function size() {
        if (!image.naturalWidth || !dialog.open) return;
        const fit = Math.min(1, (viewport.clientWidth - 16) / image.naturalWidth, (viewport.clientHeight - 16) / image.naturalHeight);
        image.style.width = `${image.naturalWidth * fit * zoom}px`;
        image.style.height = `${image.naturalHeight * fit * zoom}px`;
        control('out').disabled = zoom <= 1;
        control('in').disabled = zoom >= 2.5;
        dialog.querySelector('[role=status]').textContent = `${index + 1} / ${images.length} · ${Math.round(zoom * 100)}% · Scroll to pan`;
    }
    function show() {
        zoom = 1; image.alt = label; image.src = images[index] || fallback;
        control('previous').hidden = control('next').hidden = images.length < 2;
        viewport.scrollTo(0, 0); size();
    }
    function move(step) { index = (index + step + images.length) % images.length; show(); }
    function setZoom(value) { zoom = Math.max(1, Math.min(2.5, value)); size(); if (zoom === 1) viewport.scrollTo(0, 0); }
    image.addEventListener('load', size);
    image.addEventListener('dblclick', () => setZoom(zoom === 1 ? 2 : 1));
    let drag;
    image.addEventListener('pointerdown', event => {
        if (event.pointerType !== 'mouse' || event.button !== 0 || zoom === 1) return;
        event.preventDefault();
        drag = {x:event.clientX, y:event.clientY, left:viewport.scrollLeft, top:viewport.scrollTop};
        image.setPointerCapture(event.pointerId);
    });
    image.addEventListener('pointermove', event => {
        if (!drag) return;
        viewport.scrollLeft = drag.left - (event.clientX - drag.x);
        viewport.scrollTop = drag.top - (event.clientY - drag.y);
    });
    image.addEventListener('pointerup', () => { drag = null; });
    image.addEventListener('pointercancel', () => { drag = null; });
    image.addEventListener('lostpointercapture', () => { drag = null; });
    dialog.addEventListener('click', event => {
        const action = event.target.closest('[data-action]')?.dataset.action;
        if (action === 'close') dialog.close();
        else if (action === 'previous') move(-1);
        else if (action === 'next') move(1);
        else if (action === 'in') setZoom(zoom + .5);
        else if (action === 'out') setZoom(zoom - .5);
        else if (action === 'reset') setZoom(1);
        else if ([dialog, viewport, dialog.querySelector('.product-viewer-canvas')].includes(event.target)) dialog.close();
    });
    dialog.addEventListener('keydown', event => {
        event.stopPropagation();
        if (event.key === 'ArrowLeft') { event.preventDefault(); move(-1); }
        if (event.key === 'ArrowRight') { event.preventDefault(); move(1); }
    });
    dialog.addEventListener('close', () => {
        document.body.style.overflow = previousOverflow;
        previousFocus?.focus({preventScroll:true});
    });
    new ResizeObserver(size).observe(viewport);
    window.MoslaProductViewer = {
        get isOpen() { return dialog.open; },
        open(urls, selected = 0, name = 'Product image', trigger = document.activeElement) {
            images = urls.filter(Boolean); if (!images.length) images = [fallback];
            index = Math.max(0, Math.min(selected, images.length - 1)); label = name;
            previousFocus = trigger; previousOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden'; dialog.showModal(); show();
        }
    };

    const stage = document.querySelector('[data-product-image-stage]');
    if (!stage) return;
    const main = stage.querySelector('img');
    const thumbs = [...document.querySelectorAll('[data-product-thumbnail]')];
    const resetHover = () => { main.style.transform = ''; main.style.transformOrigin = ''; };
    stage.addEventListener('pointermove', event => {
        if (event.pointerType !== 'mouse' || !matchMedia('(hover: hover) and (pointer: fine)').matches || !main.naturalWidth) return;
        const rect = stage.getBoundingClientRect();
        const left = rect.left + (rect.width - main.offsetWidth) / 2;
        const top = rect.top + (rect.height - main.offsetHeight) / 2;
        main.style.transformOrigin = `${Math.max(0, Math.min(100, (event.clientX - left) / main.offsetWidth * 100))}% ${Math.max(0, Math.min(100, (event.clientY - top) / main.offsetHeight * 100))}%`;
        main.style.transform = 'scale(2)';
    });
    stage.addEventListener('pointerleave', resetHover);
    main.addEventListener('load', resetHover);
    stage.addEventListener('click', () => {
        resetHover();
        const urls = [...new Set(thumbs.map(button => new URL(button.dataset.productThumbnail, document.baseURI).href))];
        if (!urls.includes(main.src)) urls.unshift(main.src);
        window.MoslaProductViewer.open(urls, urls.indexOf(main.src), main.alt, stage);
    });
    thumbs.forEach(button => button.addEventListener('click', () => window.pdShowImage(button.dataset.productThumbnail, button)));
})();
