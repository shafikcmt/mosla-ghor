{{--
    Quick wholesale enquiry popup opened by the "দাম জানুন" button
    (partials.storefront.wholesale-quick-actions). Only quantity, name and phone
    are asked; it posts to products.enquiry.store as JSON so the buyer never
    leaves the listing. Name/phone are remembered on this device for the next one.
--}}
@php
    $qeCustomer = auth()->check() && auth()->user()->role === 'customer' ? auth()->user()->customer : null;
    $qeName  = $qeCustomer->name ?? (auth()->user()->name ?? '');
    $qePhone = $qeCustomer->mobile_number ?? '';
    $qeArea  = $qeCustomer->last_full_address ?? '';
    $qeWaDigits = preg_replace('/\D+/', '', \App\Models\WebsiteSetting::get('whatsapp_number'));
    $qeWa = $qeWaDigits === '' ? '' : (str_starts_with($qeWaDigits, '0') ? '88'.$qeWaDigits : $qeWaDigits);
@endphp
<div id="ms-qe" class="fixed inset-0 z-[70] hidden" role="dialog" aria-modal="true" aria-labelledby="ms-qe-title">
    <div class="absolute inset-0 bg-black/50" onclick="msQuickEnquiryClose()"></div>
    <div class="absolute inset-x-0 bottom-0 sm:inset-auto sm:top-1/2 sm:left-1/2 sm:-translate-x-1/2 sm:-translate-y-1/2 sm:w-[420px] bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl max-h-[92vh] overflow-y-auto">
        <div class="flex items-center gap-3 p-4 border-b border-gray-100">
            <img id="ms-qe-img" src="" alt="" class="w-12 h-12 rounded-lg object-cover bg-gray-100 hidden">
            <div class="flex-1 min-w-0">
                <p class="text-[11px] text-orange-700 font-semibold">পাইকারি দাম জানুন</p>
                <h2 id="ms-qe-title" class="font-semibold text-[#14532d] leading-snug truncate"></h2>
            </div>
            <button type="button" onclick="msQuickEnquiryClose()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none px-1" aria-label="বন্ধ করুন">&times;</button>
        </div>

        <form id="ms-qe-form" class="p-4 space-y-3" novalidate>
            <input type="hidden" name="product_variant_id" value="">
            <div>
                <label class="block text-xs text-gray-500 mb-1">কতটুকু লাগবে? <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <input type="number" name="quantity_kg" min="0.01" step="0.01" required inputmode="decimal" placeholder="যেমন: 25"
                           class="flex-1 min-w-0 border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    <select name="quantity_unit" class="w-32 border border-gray-200 rounded-lg px-2 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        <option value="kg">কেজি</option>
                        <option value="bag">বস্তা</option>
                        <option value="carton">কার্টন</option>
                        <option value="piece">পিস</option>
                        <option value="packet">প্যাকেট</option>
                        <option value="ton">টন</option>
                    </select>
                </div>
                <p id="ms-qe-moq" class="text-[11px] text-gray-400 mt-1 hidden"></p>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">আপনার নাম <span class="text-red-500">*</span></label>
                    <input type="text" name="customer_name" required maxlength="100" autocomplete="name" value="{{ $qeName }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">মোবাইল নম্বর <span class="text-red-500">*</span></label>
                    <input type="tel" name="customer_phone" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="01XXXXXXXXX" value="{{ $qePhone }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">এলাকা / জেলা <span class="text-gray-300">(ঐচ্ছিক)</span></label>
                <input type="text" name="delivery_location" maxlength="255" placeholder="যেমন: মিরপুর, ঢাকা" value="{{ $qeArea }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <p id="ms-qe-error" class="hidden text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2"></p>

            <button type="submit" id="ms-qe-submit"
                    class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold py-3 rounded-xl transition-colors disabled:opacity-60">
                দাম জানতে চাই
            </button>
            <p class="text-[11px] text-gray-400 text-center">লগইন লাগবে না। আমাদের টিম দ্রুত আপনাকে কল/WhatsApp করে দাম জানাবে।</p>
        </form>

        <div id="ms-qe-done" class="hidden p-6 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-green-100 text-[#14532d] flex items-center justify-center text-3xl">✓</div>
            <h3 class="mt-3 font-semibold text-[#14532d]">আপনার enquiry পাঠানো হয়েছে</h3>
            <p id="ms-qe-done-msg" class="mt-1 text-sm text-gray-500"></p>
            <div class="mt-4 grid gap-2">
                @if($qeWa)
                <a id="ms-qe-done-wa" href="https://wa.me/{{ $qeWa }}" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#1ebe5b] text-white font-semibold py-2.5 rounded-xl">
                    আরও দ্রুত দাম পেতে WhatsApp করুন
                </a>
                @endif
                <button type="button" onclick="msQuickEnquiryClose()" class="border border-gray-200 text-gray-600 font-semibold py-2.5 rounded-xl hover:bg-gray-50">ঠিক আছে</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const modal = document.getElementById('ms-qe');
    const form  = document.getElementById('ms-qe-form');
    const done  = document.getElementById('ms-qe-done');
    const err   = document.getElementById('ms-qe-error');
    const submitBtn = document.getElementById('ms-qe-submit');
    const csrf  = @json(csrf_token());
    const waBase = @json($qeWa ? 'https://wa.me/'.$qeWa : '');
    let action = '', productName = '', moq = 0;

    function store(key, val) { try { localStorage.setItem(key, val); } catch (e) {} }
    function read(key) { try { return localStorage.getItem(key) || ''; } catch (e) { return ''; } }
    function showError(msg) { err.textContent = msg; err.classList.remove('hidden'); }

    window.msQuickEnquiry = function (btn) {
        const d = btn.dataset;
        action = d.action; productName = d.name || ''; moq = parseFloat(d.moq) || 0;
        document.getElementById('ms-qe-title').textContent = productName;
        const img = document.getElementById('ms-qe-img');
        if (d.image) { img.src = d.image; img.alt = productName; img.classList.remove('hidden'); } else { img.classList.add('hidden'); }

        form.reset();
        form.quantity_kg.value = moq || '';
        form.quantity_kg.min = moq || 0.01;
        form.quantity_unit.value = d.unit || 'kg';
        form.product_variant_id.value = d.variant || '';
        const moqEl = document.getElementById('ms-qe-moq');
        if (moq) { moqEl.textContent = 'সর্বনিম্ন অর্ডার: ' + d.moq + ' ' + (d.unit || 'kg'); moqEl.classList.remove('hidden'); } else { moqEl.classList.add('hidden'); }
        // Remember the buyer on this device so the next enquiry is one tap.
        if (!form.customer_name.value) form.customer_name.value = read('ms_qe_name');
        if (!form.customer_phone.value) form.customer_phone.value = read('ms_qe_phone');
        if (!form.delivery_location.value) form.delivery_location.value = read('ms_qe_area');

        err.classList.add('hidden');
        form.classList.remove('hidden'); done.classList.add('hidden');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => (form.customer_name.value && form.customer_phone.value ? form.quantity_kg : form.customer_name).focus(), 50);
    };

    window.msQuickEnquiryClose = function () {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    };
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) msQuickEnquiryClose(); });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        err.classList.add('hidden');
        const qty = parseFloat(form.quantity_kg.value);
        const name = form.customer_name.value.trim();
        const phone = form.customer_phone.value.trim();
        if (!(qty > 0)) return showError('কতটুকু লাগবে লিখুন।');
        if (moq && qty < moq) return showError('সর্বনিম্ন অর্ডার ' + moq + ' ' + form.quantity_unit.value + '।');
        if (!name) return showError('আপনার নাম লিখুন।');
        if (!/^[0-9+\-\s]{6,20}$/.test(phone)) return showError('সঠিক মোবাইল নম্বর দিন।');

        submitBtn.disabled = true; submitBtn.textContent = 'পাঠানো হচ্ছে...';
        try {
            const res = await fetch(action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            if (res.status === 401 && data.login_url) { window.location.href = data.login_url; return; }
            if (!res.ok) {
                const first = data.errors ? Object.values(data.errors)[0] : null;
                return showError((first && first[0]) || data.message || 'পাঠানো যায়নি, আবার চেষ্টা করুন।');
            }
            store('ms_qe_name', name); store('ms_qe_phone', phone); store('ms_qe_area', form.delivery_location.value.trim());
            document.getElementById('ms-qe-done-msg').textContent = 'MoslaMart টিম শীঘ্রই আপনার নম্বরে দাম জানাবে।';
            const wa = document.getElementById('ms-qe-done-wa');
            if (wa && waBase) {
                wa.href = waBase + '?text=' + encodeURIComponent('আসসালামু আলাইকুম, আমি "' + productName + '" ' + qty + ' ' + form.quantity_unit.value + ' পাইকারি নিতে চাই। নাম: ' + name + ', মোবাইল: ' + phone);
            }
            form.classList.add('hidden'); done.classList.remove('hidden');
        } catch (ex) {
            showError('ইন্টারনেট সংযোগ দেখে আবার চেষ্টা করুন।');
        } finally {
            submitBtn.disabled = false; submitBtn.textContent = 'দাম জানতে চাই';
        }
    });
})();
</script>
