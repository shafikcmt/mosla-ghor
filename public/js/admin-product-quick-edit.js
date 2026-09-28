// Admin product list — inline "দ্রুত সম্পাদনা" (quick edit). Plain JS, no libraries.
(() => {
    const dialog = document.querySelector('[data-qe-dialog]');
    if (!dialog) return;
    const form = dialog.querySelector('[data-qe-form]');
    const saveBtn = dialog.querySelector('[data-qe-save]');
    const toast = document.querySelector('[data-qe-toast]');
    const field = name => form.elements.namedItem(name);
    const group = name => dialog.querySelector(`[data-qe-group="${name}"]`);
    let row = null;
    let state = null;
    let toastTimer = null;

    const money = n => '৳' + Math.round(Number(n)).toLocaleString('en-US');
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const clearErrors = () => dialog.querySelectorAll('[data-error]').forEach(el => { el.hidden = true; el.textContent = ''; });
    const showError = (key, message) => {
        const el = dialog.querySelector(`[data-error="${key}"]`) || dialog.querySelector('[data-error="_general"]');
        el.textContent = message;
        el.hidden = false;
    };

    // Price is editable only for non-variant products with retail switched on.
    const updateGroups = () => {
        group('price').hidden = state.has_variants || !field('show_in_retail').checked;
        group('variant-note').hidden = !state.has_variants;
        group('stock').hidden = state.unit_managed;
        group('unit-note').hidden = !state.unit_managed;
    };

    const open = tr => {
        row = tr;
        state = JSON.parse(tr.dataset.qe);
        clearErrors();
        dialog.querySelector('[data-qe-title]').textContent = tr.dataset.qeName;
        dialog.querySelector('[data-qe-edit-link]').href = state.edit_url;
        field('is_active').checked = state.is_active;
        field('show_in_retail').checked = state.show_in_retail;
        field('show_in_wholesale').checked = state.show_in_wholesale;
        field('retail_price_1kg').value = state.retail_price_1kg > 0 ? state.retail_price_1kg : '';
        field('stock').value = state.stock;
        field('low_stock_threshold').value = state.low_stock_threshold;
        field('sort_order').value = state.sort_order;
        saveBtn.disabled = false;
        updateGroups();
        dialog.showModal();
        field('is_active').focus();
    };

    const close = () => { if (dialog.open) dialog.close(); };
    dialog.addEventListener('close', () => row?.querySelector('[data-qe-open]')?.focus());

    const payload = () => {
        const data = {
            is_active: field('is_active').checked,
            show_in_retail: field('show_in_retail').checked,
            show_in_wholesale: field('show_in_wholesale').checked,
            low_stock_threshold: field('low_stock_threshold').value === '' ? null : field('low_stock_threshold').value,
            sort_order: field('sort_order').value === '' ? null : field('sort_order').value,
        };
        if (!group('price').hidden) data.retail_price_1kg = field('retail_price_1kg').value === '' ? null : field('retail_price_1kg').value;
        if (!state.unit_managed) data.stock = field('stock').value;
        return data;
    };

    const renderRow = (p, packs) => {
        row.dataset.qe = JSON.stringify(p);
        row.querySelector('[data-cell="order"]').textContent = p.sort_order || row.dataset.qeIndex;
        const active = p.show_in_retail ? packs.filter(pack => pack.is_active) : [];
        row.querySelector('[data-cell="price"]').innerHTML = esc(money(p.retail_price_1kg))
            + (active.length ? `<div class="qe-packs">${active.map(pack => esc(`${pack.label} ${money(pack.final_price)}${pack.is_manual_override ? ' ✎' : ''}`)).join(' · ')}</div>` : '');
        row.querySelector('[data-cell="stock"]').innerHTML = esc(p.stock) + (p.stock === 0 ? ' <span class="text-xs text-red-500 ml-1">(শেষ)</span>' : '');
        row.querySelector('[data-cell="status"]').innerHTML = '<div class="flex flex-wrap items-center justify-center gap-1">'
            + (p.is_active
                ? '<span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">সক্রিয়</span>'
                : '<span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">নিষ্ক্রিয়</span>')
            + (p.show_in_retail ? '<span class="bg-green-50 text-green-700 border border-green-200 text-[10px] px-2 py-0.5 rounded-full">খুচরা</span>' : '')
            + (p.show_in_wholesale ? '<span class="bg-blue-50 text-blue-700 border border-blue-200 text-[10px] px-2 py-0.5 rounded-full">পাইকারি</span>' : '')
            + '</div>';
        row.classList.add('qe-flash');
        setTimeout(() => row.classList.remove('qe-flash'), 1600);
    };

    const showToast = (message, isError = false) => {
        toast.textContent = message;
        toast.classList.toggle('qe-toast-error', isError);
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3000);
    };

    document.addEventListener('click', event => {
        const button = event.target.closest('[data-qe-open]');
        if (button) open(button.closest('[data-qe-row]'));
    });
    dialog.querySelectorAll('[data-qe-close]').forEach(b => b.addEventListener('click', close));
    // Click on the backdrop (outside the form) closes without saving; Esc is native to <dialog>.
    dialog.addEventListener('click', event => { if (event.target === dialog) close(); });
    field('show_in_retail').addEventListener('change', updateGroups);

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (saveBtn.disabled) return;
        clearErrors();
        saveBtn.disabled = true;
        saveBtn.textContent = 'সংরক্ষণ হচ্ছে…';
        try {
            const response = await fetch(state.update_url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': dialog.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: JSON.stringify(payload()),
            });
            const json = await response.json().catch(() => ({}));
            if (response.ok) {
                renderRow(json.product, json.packs || []);
                Object.entries(json.stats || {}).forEach(([key, value]) => {
                    const el = document.querySelector(`[data-stat="${key}"]`);
                    if (el) el.textContent = value;
                });
                close();
                showToast(json.message || 'সংরক্ষণ হয়েছে।');
            } else if (response.status === 422 && json.errors) {
                Object.entries(json.errors).forEach(([key, messages]) => showError(key, messages[0]));
            } else if (response.status === 419) {
                showError('_general', 'সেশনের মেয়াদ শেষ। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।');
            } else {
                showError('_general', json.message && response.status < 500 ? json.message : 'সংরক্ষণ করা যায়নি। আবার চেষ্টা করুন।');
            }
        } catch (e) {
            showError('_general', 'নেটওয়ার্ক সমস্যা। ইন্টারনেট সংযোগ দেখে আবার চেষ্টা করুন।');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'সংরক্ষণ';
        }
    });
})();
