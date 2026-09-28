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
    editor.querySelector('.pe-errors')?.focus();
});
