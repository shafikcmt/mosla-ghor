document.querySelectorAll('[data-product-editor]').forEach(editor => {
    const updateChannels = () => {
        const selected = {};
        editor.querySelectorAll('[data-channel]').forEach(input => { selected[input.dataset.channel] = input.checked; });
        editor.querySelectorAll('[data-channel-section]').forEach(section => {
            section.hidden = section.disabled = !selected[section.dataset.channelSection];
        });
        editor.querySelector('.pe-channel-message').textContent = selected.retail || selected.wholesale
            ? 'বিক্রয় মাধ্যম বদলালেও সংরক্ষিত প্যাকের তথ্য থাকবে।' : 'অন্তত একটি বিক্রয় মাধ্যম বেছে নিন।';
    };
    editor.querySelectorAll('[data-channel]').forEach(input => input.addEventListener('change', updateChannels));
    updateChannels();
    editor.addEventListener('change', event => {
        if (!event.target.matches('[data-preview-input]')) return;
        const img = event.target.closest('[data-media-preview]').querySelector('[data-preview]');
        const file = event.target.files[0];
        if (!img.dataset.original) img.dataset.original = img.getAttribute('src') || '';
        if (img.dataset.objectUrl) URL.revokeObjectURL(img.dataset.objectUrl);
        img.dataset.objectUrl = file ? URL.createObjectURL(file) : '';
        img.src = img.dataset.objectUrl || img.dataset.original;
        img.hidden = !img.getAttribute('src');
    });
    let variantIndex = Math.max(-1, ...Array.from(editor.querySelectorAll('[data-variant]'), card => Number(card.dataset.field.match(/^new_variants\[(\d+)\]$/)?.[1] ?? -1))) + 1;
    editor.addEventListener('click', event => {
        const button = event.target.closest('button');
        if (!button) return;
        if (button.matches('[data-variant-add]')) {
            const list = editor.querySelector('[data-variant-list]');
            list.insertAdjacentHTML('beforeend', editor.querySelector('[data-variant-template]').innerHTML.replaceAll('__INDEX__', variantIndex++));
            list.lastElementChild.querySelector('input').focus();
        }
        if (button.matches('[data-variant-remove]')) button.closest('[data-variant]').remove();
        if (button.matches('[data-attribute-remove]')) {
            const row = button.closest('.pe-attribute');
            if (row.parentElement.children.length === 1) row.querySelectorAll('input').forEach(input => { input.value = ''; });
            else row.remove();
        }
        if (button.matches('[data-attribute-add]')) {
            const card = button.closest('[data-variant]');
            const list = card.querySelector('[data-attributes]');
            if (list.children.length >= 10) return;
            const row = list.firstElementChild.cloneNode(true);
            const index = Math.max(...Array.from(list.querySelectorAll('input'), input => Number(input.name.match(/\[attributes\]\[(\d+)\]/)[1]))) + 1;
            row.querySelectorAll('input').forEach(input => { input.name = input.name.replace(/\[attributes\]\[\d+\]/, `[attributes][${index}]`); input.value = ''; });
            list.append(row);
            row.querySelector('input').focus();
        }
    });
    editor.addEventListener('change', event => {
        if (!event.target.closest('[data-variant]')) return;
        const card = event.target.closest('[data-variant]');
        const inactive = !card.querySelector('input[type=checkbox][name$="[is_active]"]').checked || card.querySelector('input[name$="[_delete]"]')?.checked;
        const radio = card.querySelector('input[type=radio]');
        radio.disabled = inactive;
        if (inactive) radio.checked = false;
    });
    const tagSource = editor.querySelector('[data-tag-source]');
    const tagInput = editor.querySelector('[data-tag-input]');
    const tagList = editor.querySelector('[data-tag-list]');
    const tags = () => tagSource.value.split(/[,\r\n]+/).map(tag => tag.trim()).filter(Boolean);
    const renderTags = () => {
        tagList.replaceChildren();
        tags().forEach((tag, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'pe-button pe-secondary';
            button.textContent = tag + ' ×';
            button.setAttribute('aria-label', tag + ' সরান');
            button.addEventListener('click', () => {
                const values = tags(); values.splice(index, 1); tagSource.value = values.join(', '); renderTags(); tagInput.focus();
            });
            tagList.append(button);
        });
    };
    const addTags = () => {
        const values = tags();
        tagInput.value.split(/[,\r\n]+/).forEach(value => {
            value = value.trim();
            if (value && !values.some(tag => tag.toLowerCase() === value.toLowerCase())) values.push(value);
        });
        if (values.length > 20 || values.some(value => [...value].length > 80)) {
            tagInput.setCustomValidity('সর্বোচ্চ ২০টি ট্যাগ, প্রতিটি ৮০ অক্ষর।');
            tagInput.reportValidity(); return false;
        }
        tagInput.setCustomValidity(''); tagSource.value = values.join(', '); tagInput.value = ''; renderTags(); return true;
    };
    tagInput.addEventListener('input', () => tagInput.setCustomValidity(''));
    tagInput.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ',') { event.preventDefault(); addTags(); } });
    editor.querySelector('[data-tag-add]').addEventListener('click', addTags);
    editor.querySelector('form').addEventListener('submit', event => { if (!addTags()) event.preventDefault(); });
    // Double-submit guard: disable both save buttons once the submit really goes ahead.
    // Done on the next tick so the clicked button's name/value (after_save=new) is still sent.
    const submitButtons = () => editor.querySelectorAll('[data-pe-submit]');
    editor.querySelector('form').addEventListener('submit', event => {
        if (event.defaultPrevented) return;
        setTimeout(() => submitButtons().forEach(button => { button.disabled = true; }), 0);
    });
    // Coming back via the browser's Back button restores a disabled page from cache: re-enable.
    window.addEventListener('pageshow', () => submitButtons().forEach(button => { button.disabled = false; }));
    tagSource.hidden = true;
    editor.querySelector('[data-tags]').hidden = false;
    renderTags();
    const seo = editor.querySelector('[data-seo]');
    if (seo) {
        const field = name => editor.querySelector(`[name="${name}"]`);
        const bn = n => String(n).replace(/\d/g, d => '০১২৩৪৫৬৭৮৯'[d]);
        const strip = html => { const div = document.createElement('div'); div.innerHTML = html; return div.textContent.replace(/\s+/g, ' ').trim(); };
        const limit = (text, max) => [...text].length > max ? [...text].slice(0, max).join('').trimEnd() + '...' : text;
        const slugify = text => text.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        const updateSeo = () => {
            const name = field('name_bn')?.value.trim() || field('name_en')?.value.trim() || 'পণ্যের নাম';
            const autoDesc = limit(strip(field('short_description')?.value || field('description')?.value || '') || name, 155);
            field('meta_title').placeholder = name;
            field('meta_description').placeholder = autoDesc;
            field('meta_keywords').placeholder = tagSource.value.trim() || 'খালি রাখলে ট্যাগগুলো ব্যবহার হবে';
            seo.querySelector('[data-serp-title]').textContent = field('meta_title').value.trim() || `${name} — ${seo.dataset.siteName}`;
            seo.querySelector('[data-serp-desc]').textContent = field('meta_description').value.trim() || autoDesc;
            seo.querySelector('[data-serp-url]').textContent = seo.dataset.baseUrl + (field('slug')?.value.trim() || slugify(field('name_en')?.value || '') || '…');
            seo.querySelectorAll('[data-seo-count]').forEach(input => {
                const length = [...input.value].length;
                const counter = input.closest('label').querySelector('[data-seo-counter]');
                counter.textContent = bn(length);
                counter.classList.toggle('pe-over', length > Number(input.dataset.seoCount));
            });
        };
        editor.addEventListener('input', event => { if (event.target.name) updateSeo(); });
        tagList.addEventListener('click', updateSeo);
        editor.querySelector('[data-tag-add]').addEventListener('click', updateSeo);
        tagInput.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ',') setTimeout(updateSeo); });
        updateSeo();
    }
    // ── One image per request (so nginx/PHP request limits never apply to the whole form) ──
    // After the browser resize, each chosen image is uploaded on its own; the form then sends
    // only tokens. Without JavaScript the plain file inputs still work (server fallback).
    const uploadUrl = editor.dataset.uploadUrl;
    const csrfToken = editor.querySelector('input[name=_token]')?.value || '';
    let uploading = 0;
    const uploadMessage = text => {
        let box = editor.querySelector('[data-upload-block]');
        if (!box) {
            box = document.createElement('p');
            box.className = 'pe-note';
            box.setAttribute('data-upload-block', '');
            box.setAttribute('role', 'alert');
            editor.querySelector('.pe-actions').insertAdjacentElement('beforebegin', box);
        }
        box.textContent = text;
        box.hidden = !text;
        if (text) box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };
    const listFor = input => {
        const anchor = input.closest('label') || input;
        let list = anchor.nextElementSibling;
        if (!list || !list.matches('[data-upload-list]')) {
            list = document.createElement('div');
            list.className = 'pe-uploads';
            list.setAttribute('data-upload-list', '');
            anchor.insertAdjacentElement('afterend', list);
        }
        return list;
    };
    const uploadOne = (input, file, item) => {
        const bar = item.querySelector('progress');
        const status = item.querySelector('[data-status]');
        const retry = item.querySelector('[data-retry]');
        item.dataset.state = 'uploading';
        bar.hidden = false; bar.value = 0; retry.hidden = true;
        status.textContent = 'আপলোড হচ্ছে…';
        uploading++;
        const form = new FormData();
        form.append('file', file);
        form.append('kind', input.dataset.asyncUpload);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', uploadUrl);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.upload.onprogress = e => { if (e.lengthComputable) bar.value = Math.round(e.loaded / e.total * 100); };
        xhr.onloadend = () => {
            uploading--;
            bar.hidden = true;
            let json = null;
            try { json = JSON.parse(xhr.responseText); } catch (e) { json = null; }
            if (xhr.status === 200 && json && json.token) {
                item.dataset.state = 'done';
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = input.dataset.tokenName;
                hidden.value = json.token;
                item.appendChild(hidden);
                if (json.url) item.querySelector('img').src = json.url;
                status.textContent = '✓ আপলোড হয়েছে — সংরক্ষণ করলে যোগ হবে';
            } else {
                item.dataset.state = 'failed';
                let msg = 'আপলোড হয়নি। আবার চেষ্টা করুন।';
                if (xhr.status === 413) msg = 'ছবিটি সার্ভারের সীমার চেয়ে বড় — ছোট ছবি দিন।';
                else if (xhr.status === 419) msg = 'সেশনের মেয়াদ শেষ — পেজ রিফ্রেশ করুন।';
                else if (json && json.errors) msg = Object.values(json.errors)[0][0];
                else if (json && json.message) msg = json.message;
                else if (xhr.status === 0) msg = 'সংযোগ বিচ্ছিন্ন — আবার চেষ্টা করুন।';
                status.textContent = msg;
                retry.hidden = false;
            }
            if (!uploading && !editor.querySelector('.pe-upload[data-state=failed]')) uploadMessage('');
        };
        xhr.send(form);
    };
    const addItem = (input, file) => {
        const list = listFor(input);
        if (input.dataset.asyncUpload !== 'gallery') {
            // Single-image fields: the newest choice replaces earlier ones.
            list.querySelectorAll('.pe-upload:not([data-state=uploading])').forEach(el => el.remove());
        }
        const item = document.createElement('div');
        item.className = 'pe-upload';
        item.innerHTML = '<img alt=""><div class="pe-upload-body"><small data-name></small><progress max="100" value="0"></progress>'
            + '<small data-status></small><span class="pe-upload-actions">'
            + '<button type="button" class="pe-button pe-secondary" data-retry hidden>আবার চেষ্টা</button>'
            + '<button type="button" class="pe-button pe-secondary" data-remove>সরান</button></span></div>';
        item.querySelector('[data-name]').textContent = file.name;
        item.querySelector('img').src = URL.createObjectURL(file);
        item.querySelector('[data-retry]').addEventListener('click', () => uploadOne(input, file, item));
        list.appendChild(item);
        uploadOne(input, file, item);
    };
    editor.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove]');
        if (!remove) return;
        const item = remove.closest('.pe-upload');
        if (item && item.dataset.state !== 'uploading') item.remove();
    });
    editor.addEventListener('change', event => {
        const input = event.target;
        if (!uploadUrl || !input.matches || !input.matches('input[type=file][data-async-upload]')) return;
        // Wait for image-resize.js to hand over the resized files (it re-dispatches 'change').
        if (input.hasAttribute('data-resize') && !input.dataset.msResized) return;
        const files = Array.from(input.files || []);
        if (!files.length || !window.FormData || !window.XMLHttpRequest) return;
        files.forEach(file => addItem(input, file));
        input.value = ''; // the form sends tokens, not the files
    });
    editor.querySelector('form').addEventListener('submit', event => {
        if (uploading > 0) {
            event.preventDefault();
            uploadMessage('ছবি আপলোড শেষ হওয়া পর্যন্ত অপেক্ষা করুন, তারপর সংরক্ষণ করুন।');
        } else if (editor.querySelector('.pe-upload[data-state=failed]')) {
            event.preventDefault();
            uploadMessage('একটি ছবি আপলোড হয়নি — “আবার চেষ্টা” চাপুন অথবা ছবিটি সরিয়ে দিন।');
        }
    }, true); // capture: runs before the double-submit guard
    editor.querySelector('.pe-errors')?.focus();
});
