// MoslaMart: resize images in the browser before upload, so big phone photos fit the
// server's small upload limits. Plain JS, no libraries.
//
// Acts on <input type="file" data-resize data-resize-max data-resize-quality
// data-resize-format="webp|jpeg" [data-resize-crop="1200x630"]> (attributes come from
// ImageOptimizer::inputAttributes, so browser and server use the same profile).
// Canvas output never contains EXIF/GPS. Any failure keeps the original file.
(function () {
    'use strict';
    // Server limits (bytes) from the <script> tag: measured nginx/PHP limit or PHP's settings.
    var me = document.currentScript;
    var LIMIT_REQUEST = me ? parseInt(me.getAttribute('data-max-request'), 10) || 0 : 0;
    var LIMIT_FILE = me ? parseInt(me.getAttribute('data-max-file'), 10) || 0 : 0;
    // Aim each resized image well under the limits (never below 300 KB).
    var MAX_BYTES = Math.max(300 * 1024, Math.min(1.8 * 1024 * 1024,
        LIMIT_FILE ? LIMIT_FILE * 0.9 : Infinity, LIMIT_REQUEST ? LIMIT_REQUEST * 0.9 : Infinity));
    var pending = 0;
    var mb = function (b) { return (b / 1048576).toFixed(1) + ' MB'; };

    function setBusy(form, busy) {
        if (!form) return;
        form.querySelectorAll('button[type=submit], input[type=submit]').forEach(function (b) {
            if (busy) { if (!b.dataset.msResizeWas) b.dataset.msResizeWas = b.disabled ? '1' : '0'; b.disabled = true; }
            else if (b.dataset.msResizeWas !== undefined) { b.disabled = b.dataset.msResizeWas === '1'; delete b.dataset.msResizeWas; }
        });
    }

    function status(input, text) {
        var el = input.parentNode && input.parentNode.querySelector('[data-resize-status]');
        if (!el) {
            el = document.createElement('small');
            el.setAttribute('data-resize-status', '');
            el.style.display = 'block';
            el.style.color = '#596579';
            input.insertAdjacentElement('afterend', el);
        }
        el.textContent = text;
    }

    function loadBitmap(file) {
        // createImageBitmap applies EXIF orientation; fall back to <img> (browsers auto-orient too).
        if (window.createImageBitmap) {
            return createImageBitmap(file, { imageOrientation: 'from-image' }).catch(function () { return loadImg(file); });
        }
        return loadImg(file);
    }

    function loadImg(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('decode')); };
            img.src = url;
        });
    }

    function toBlob(canvas, type, quality) {
        return new Promise(function (resolve) { canvas.toBlob(resolve, type, quality); });
    }

    async function encode(canvas, format, quality, keepAlpha) {
        var types = format === 'jpeg' ? ['image/jpeg'] : ['image/webp', keepAlpha ? 'image/png' : 'image/jpeg'];
        for (var i = 0; i < types.length; i++) {
            var q = quality;
            var blob = await toBlob(canvas, types[i], q);
            // Browsers without WebP encoding silently return PNG: treat as unsupported.
            if (!blob || blob.type !== types[i]) continue;
            while (blob.size > MAX_BYTES && types[i] !== 'image/png' && q > 0.5) {
                q = Math.round((q - 0.1) * 100) / 100;
                blob = await toBlob(canvas, types[i], q);
            }
            return blob;
        }
        return null;
    }

    async function resizeFile(file, opts) {
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return null;
        var src = await loadBitmap(file);
        var w = src.width, h = src.height, canvas = document.createElement('canvas'), ctx;
        if (opts.crop) {
            // Cover-crop to the exact share size (og_image), centred.
            var tw = opts.crop[0], th = opts.crop[1];
            var scale = Math.max(tw / w, th / h);
            var sw = tw / scale, sh = th / scale;
            canvas.width = tw; canvas.height = th;
            ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, tw, th); // JPEG has no transparency
            ctx.drawImage(src, (w - sw) / 2, (h - sh) / 2, sw, sh, 0, 0, tw, th);
        } else {
            var ratio = Math.min(1, opts.max / Math.max(w, h)); // never upscale
            canvas.width = Math.max(1, Math.round(w * ratio));
            canvas.height = Math.max(1, Math.round(h * ratio));
            ctx = canvas.getContext('2d');
            ctx.imageSmoothingQuality = 'high';
            if (opts.format === 'jpeg') { ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, canvas.width, canvas.height); }
            ctx.drawImage(src, 0, 0, canvas.width, canvas.height);
        }
        if (src.close) src.close();

        var blob = await encode(canvas, opts.format, opts.quality, file.type !== 'image/jpeg');
        if (!blob) return null;
        var fits = !opts.crop && Math.max(w, h) <= opts.max;
        // Already small enough and the new file isn't smaller → keep the original (unless it's a JPEG,
        // whose EXIF/GPS we'd rather drop, or a crop that changes the picture).
        if (fits && !opts.crop && blob.size >= file.size && file.type !== 'image/jpeg') return null;
        var ext = blob.type === 'image/webp' ? 'webp' : blob.type === 'image/png' ? 'png' : 'jpg';
        var base = (file.name || 'image').replace(/\.[^.]+$/, '') || 'image';
        return new File([blob], base + '.' + ext, { type: blob.type, lastModified: Date.now() });
    }

    async function handle(input) {
        var files = Array.prototype.slice.call(input.files || []);
        if (!files.length || !window.DataTransfer || !HTMLCanvasElement.prototype.toBlob) return;
        var crop = (input.dataset.resizeCrop || '').split('x').map(Number);
        var opts = {
            max: parseInt(input.dataset.resizeMax, 10) || 1600,
            quality: parseFloat(input.dataset.resizeQuality) || 0.8,
            format: input.dataset.resizeFormat === 'jpeg' ? 'jpeg' : 'webp',
            crop: crop.length === 2 && crop[0] && crop[1] ? crop : null
        };
        var form = input.form;
        pending++;
        setBusy(form, true);
        status(input, 'ছবি ছোট করা হচ্ছে…');
        var dt = new DataTransfer(), changed = false, before = 0, after = 0;
        for (var i = 0; i < files.length; i++) {
            var out = null;
            try { out = await resizeFile(files[i], opts); } catch (e) { out = null; }
            before += files[i].size;
            after += (out || files[i]).size;
            if (out) changed = true;
            dt.items.add(out || files[i]);
        }
        try {
            if (changed) input.files = dt.files;
        } catch (e) { changed = false; /* very old browser: keep the originals */ }
        input.dataset.msResized = '1';
        status(input, changed
            ? 'ছবি প্রস্তুত: ' + (before / 1048576).toFixed(1) + ' MB → ' + (after / 1048576).toFixed(1) + ' MB'
            : '');
        // Let page scripts (previews, size checks) see the final file. Marked as ours so it
        // is never resized again (dispatchEvent runs listeners synchronously).
        input.dataset.msResizing = '1';
        try { input.dispatchEvent(new Event('change', { bubbles: true })); }
        finally { delete input.dataset.msResizing; }
        pending--;
        if (!pending) setBusy(form, false);
    }

    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.type !== 'file' || !input.hasAttribute('data-resize')) return;
        if (input.dataset.msResizing) return; // our own re-dispatch
        delete input.dataset.msResized;
        handle(input);
    }, true);

    function guardMessage(form, text) {
        var box = form.querySelector('[data-upload-guard]');
        if (!box) {
            box = document.createElement('div');
            box.setAttribute('data-upload-guard', '');
            box.setAttribute('role', 'alert');
            box.style.cssText = 'margin:0 0 16px;padding:12px 16px;border:1px solid #fca5a5;background:#fff1f2;color:#9f1239;border-radius:8px;font-size:14px';
            form.insertBefore(box, form.firstChild);
        }
        box.textContent = text;
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Never submit while a resize is still running, or when the files can't fit the server's limits
    // (a friendly Bangla message instead of nginx's raw "413 Request Entity Too Large").
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (pending > 0) { e.preventDefault(); e.stopImmediatePropagation(); return; }
        if (!form || !form.querySelectorAll || !(LIMIT_FILE || LIMIT_REQUEST)) return;
        var total = 0, tooBig = null;
        form.querySelectorAll('input[type=file]').forEach(function (input) {
            if (input.disabled) return;
            Array.prototype.forEach.call(input.files || [], function (f) {
                total += f.size;
                if (LIMIT_FILE && f.size > LIMIT_FILE && !tooBig) tooBig = f;
            });
        });
        var text = '';
        if (tooBig) {
            text = '“' + tooBig.name + '” ফাইলটি ' + mb(tooBig.size) + ' — সার্ভার প্রতি ফাইলে ' + mb(LIMIT_FILE) +
                ' পর্যন্ত নেয়। ছোট ছবি দিন অথবা আবার বেছে নিন — আমরা নিজে থেকে ছোট করে দিই।';
        } else if (LIMIT_REQUEST && total > LIMIT_REQUEST * 0.95) {
            text = 'ছবিগুলো মোট ' + mb(total) + ' — সার্ভার একবারে ' + mb(LIMIT_REQUEST) +
                ' পর্যন্ত নেয়। কয়েকটি ছবি সরিয়ে আগে সংরক্ষণ করুন, তারপর বাকিগুলো যোগ করুন।';
        }
        if (text) {
            e.preventDefault();
            e.stopImmediatePropagation();
            guardMessage(form, text);
        }
    }, true);

    window.MoslaImageResize = { pending: function () { return pending; } };
})();
