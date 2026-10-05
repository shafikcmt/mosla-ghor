{{--
    Quick wholesale enquiry popup, opened by দাম জানুন / WhatsApp / কল করুন
    (partials.storefront.wholesale-quick-actions and the wholesale product page).

    One short step for every wholesale contact: quantity (must meet the MOQ —
    smaller amounts are pointed to the retail page), name + phone (remembered on
    this device), optional business type. The lead is saved first (JSON post to
    products.enquiry.store, contact_channel = form|whatsapp|call); only then does
    the server hand back the WhatsApp / call link. A product this device already
    enquired about in the last 7 days opens WhatsApp / call straight away.
--}}
@php
    $qeCustomer = auth()->check() && auth()->user()->role === 'customer' ? auth()->user()->customer : null;
    $qeName  = $qeCustomer->name ?? (auth()->user()->name ?? '');
    $qePhone = $qeCustomer->mobile_number ?? '';
    $qeArea  = $qeCustomer->last_full_address ?? '';
@endphp
<div id="ms-qe" class="fixed inset-0 z-[70] hidden" role="dialog" aria-modal="true" aria-labelledby="ms-qe-title">
    <div class="absolute inset-0 bg-black/50" onclick="msQuickEnquiryClose()"></div>
    <div class="absolute inset-x-0 bottom-0 sm:inset-auto sm:top-1/2 sm:left-1/2 sm:-translate-x-1/2 sm:-translate-y-1/2 sm:w-[440px] bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl max-h-[92vh] overflow-y-auto">
        <div class="flex items-center gap-3 p-4 border-b border-gray-100">
            <img id="ms-qe-img" src="" alt="" class="w-12 h-12 rounded-lg object-cover bg-gray-100 hidden">
            <div class="flex-1 min-w-0">
                <p id="ms-qe-kicker" class="text-[11px] text-orange-700 font-semibold">পাইকারি দাম জানুন</p>
                <h2 id="ms-qe-title" class="font-semibold text-[#14532d] leading-snug truncate"></h2>
            </div>
            <button type="button" onclick="msQuickEnquiryClose()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none px-1" aria-label="বন্ধ করুন">&times;</button>
        </div>

        <p id="ms-qe-lead" class="mx-4 mt-3 text-xs text-gray-600 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 hidden"></p>

        @guest
        @if(\App\Support\AuthSettings::socialProviders())
        {{-- One-tap sign in: name/email come from Google/Facebook; the popup reopens on return. --}}
        <div id="ms-qe-social" class="px-4 pt-4">
            @include('partials.social-login-buttons', ['socialRedirect' => request()->getRequestUri(), 'socialDivider' => false, 'socialOnclick' => 'return msQuickEnquirySocial(this)'])
            <div class="flex items-center gap-3 mt-4" aria-hidden="true">
                <span class="flex-1 h-px bg-gray-200"></span>
                <span class="text-xs font-semibold text-gray-400">অথবা নিচে নাম ও নম্বর দিন</span>
                <span class="flex-1 h-px bg-gray-200"></span>
            </div>
        </div>
        @endif
        @endguest

        <form id="ms-qe-form" class="p-4 space-y-3" novalidate>
            <input type="hidden" name="product_variant_id" value="">
            <input type="hidden" name="contact_channel" value="form">
            <input type="hidden" name="business_type" value="">
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
                {{-- Below the MOQ → this is a retail-size need; point to the retail page. --}}
                <div id="ms-qe-retail" class="hidden mt-2 text-xs text-orange-800 bg-orange-50 border border-orange-100 rounded-lg px-3 py-2">
                    <span id="ms-qe-retail-msg"></span>
                    <a id="ms-qe-retail-link" href="#" class="font-semibold underline whitespace-nowrap">খুচরা কিনুন →</a>
                </div>
            </div>

            <div>
                <p class="text-xs text-gray-500 mb-1.5">আপনি কী করেন? <span class="text-gray-300">(ঐচ্ছিক)</span></p>
                <div class="flex flex-wrap gap-1.5" id="ms-qe-biz">
                    @foreach(['shop' => 'দোকান', 'restaurant' => 'রেস্টুরেন্ট', 'dealer' => 'ডিলার / রিসেলার', 'other' => 'অন্যান্য'] as $val => $lbl)
                    <button type="button" data-biz="{{ $val }}" onclick="msQeBiz(this)"
                            class="px-3 py-1.5 rounded-full text-xs font-semibold border border-gray-200 text-gray-600 hover:border-[#14532d] transition">{{ $lbl }}</button>
                    @endforeach
                </div>
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
            <p class="text-[11px] text-gray-400 text-center">লগইন লাগবে না। নাম ও নম্বর এই ডিভাইসে মনে থাকবে — পরের বার শুধু পরিমাণ দিলেই হবে।</p>
        </form>

        <div id="ms-qe-done" class="hidden p-6 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-green-100 text-[#14532d] flex items-center justify-center text-3xl">✓</div>
            <h3 id="ms-qe-done-title" class="mt-3 font-semibold text-[#14532d]">আপনার enquiry পাঠানো হয়েছে</h3>
            <p id="ms-qe-done-msg" class="mt-1 text-sm text-gray-500"></p>
            <div class="mt-4 grid gap-2">
                <a id="ms-qe-done-wa" href="#" target="_blank" rel="noopener"
                   style="display:none" class="inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#1ebe5b] text-white font-semibold py-3 rounded-xl">
                    WhatsApp-এ মেসেজ পাঠান
                </a>
                <a id="ms-qe-done-tel" href="#"
                   style="display:none" class="inline-flex items-center justify-center gap-2 bg-[#c9a227] hover:bg-[#e2bb45] text-[#0f3d22] font-semibold py-3 rounded-xl">
                    📞 এখনই কল করুন
                </a>
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
    const retailBox = document.getElementById('ms-qe-retail');
    const csrf  = @json(csrf_token());
    const UNIT_BN = { kg: 'কেজি', bag: 'বস্তা', carton: 'কার্টন', piece: 'পিস', packet: 'প্যাকেট', ton: 'টন' };
    const MODES = {
        form:     { kicker: 'পাইকারি দাম জানুন', submit: 'দাম জানতে চাই', lead: '' },
        whatsapp: { kicker: 'WhatsApp-এ কথা বলুন', submit: 'WhatsApp-এ চালিয়ে যান',
                    lead: 'পাইকারি অর্ডারের জন্য পরিমাণ ও নম্বর দিন — তারপর সরাসরি WhatsApp খুলবে।' },
        call:     { kicker: 'কল করে কথা বলুন', submit: 'কল করতে চালিয়ে যান',
                    lead: 'পাইকারি অর্ডারের জন্য পরিমাণ ও নম্বর দিন — তারপর সরাসরি কল করতে পারবেন।' },
    };
    const CONTACT_KEY = 'ms_qe_contact', CONTACT_TTL = 7 * 24 * 3600 * 1000;
    let action = '', productName = '', moq = 0, moqUnit = 'kg', retailUrl = '', slug = '', mode = 'form';

    function store(key, val) { try { localStorage.setItem(key, val); } catch (e) {} }
    function read(key) { try { return localStorage.getItem(key) || ''; } catch (e) { return ''; } }
    function showError(msg) { err.textContent = msg; err.classList.remove('hidden'); }
    function contacts() { try { return JSON.parse(read(CONTACT_KEY) || '{}'); } catch (e) { return {}; } }

    // Already enquired about this product recently on this device → open directly.
    function savedContact(s) {
        const c = contacts()[s];
        return c && (Date.now() - c.at) < CONTACT_TTL ? c : null;
    }
    // direct = straight from the click (no popup blocker). After the async save a
    // blocked WhatsApp tab is fine: the success screen shows a big WhatsApp button.
    function openContact(c, m, direct) {
        if (m === 'call') { window.location.href = c.tel; return; }
        const w = window.open(c.whatsapp, '_blank');
        if (w) { try { w.opener = null; } catch (e) {} }
        else if (direct) { window.location.href = c.whatsapp; }
    }

    window.msQeBiz = function (b) {
        const on = form.business_type.value !== b.dataset.biz;
        form.business_type.value = on ? b.dataset.biz : '';
        document.querySelectorAll('#ms-qe-biz [data-biz]').forEach(x => {
            const sel = on && x === b;
            x.classList.toggle('bg-[#14532d]', sel); x.classList.toggle('text-white', sel); x.classList.toggle('border-[#14532d]', sel);
            x.classList.toggle('text-gray-600', !sel);
        });
    };

    // Quantity below the MOQ (same unit) → this is a retail-size need.
    function belowMoq() {
        const q = parseFloat(form.quantity_kg.value);
        return moq > 0 && q > 0 && form.quantity_unit.value === moqUnit && q < moq;
    }
    function refreshRetailHint() {
        const show = belowMoq();
        retailBox.classList.toggle('hidden', !show);
        if (!show) return;
        document.getElementById('ms-qe-retail-msg').textContent =
            'পাইকারি সর্বনিম্ন অর্ডার ' + moq + ' ' + (UNIT_BN[moqUnit] || moqUnit) + '। অল্প পরিমাণ নিতে চাইলে ';
        const link = document.getElementById('ms-qe-retail-link');
        link.href = retailUrl || '/';
        link.textContent = retailUrl ? 'খুচরা দামে কিনুন →' : 'খুচরা পণ্য দেখুন →';
    }
    form.quantity_kg.addEventListener('input', refreshRetailHint);
    form.quantity_unit.addEventListener('change', refreshRetailHint);

    // btn: an element inside [data-action] (card/product page), or {dataset} when
    // reopening after social sign-in. m: 'form' | 'whatsapp' | 'call'.
    let lastData = null;
    window.msQuickEnquiry = function (btn, m) {
        const holder = (btn.closest && btn.closest('[data-action]')) || btn;
        const d = Object.assign({}, holder.dataset);
        mode = MODES[m || d.mode] ? (m || d.mode) : 'form';
        d.mode = mode;
        lastData = d;
        action = d.action; productName = d.name || ''; slug = d.slug || '';
        moq = parseFloat(d.moq) || 0; moqUnit = d.unit || 'kg'; retailUrl = d.retailUrl || '';

        if (mode !== 'form') {
            const c = savedContact(slug);
            if (c) { openContact(c, mode, true); return; }
        }

        document.getElementById('ms-qe-title').textContent = productName;
        document.getElementById('ms-qe-kicker').textContent = MODES[mode].kicker;
        const lead = document.getElementById('ms-qe-lead');
        lead.textContent = MODES[mode].lead; lead.classList.toggle('hidden', !MODES[mode].lead);
        submitBtn.textContent = MODES[mode].submit;
        const img = document.getElementById('ms-qe-img');
        if (d.image) { img.src = d.image; img.alt = productName; img.classList.remove('hidden'); } else { img.classList.add('hidden'); }

        form.reset();
        form.contact_channel.value = mode;
        form.business_type.value = '';
        document.querySelectorAll('#ms-qe-biz [data-biz]').forEach(x => x.classList.remove('bg-[#14532d]', 'text-white', 'border-[#14532d]'));
        form.quantity_kg.value = d.qty || moq || '';
        form.quantity_unit.value = d.qunit || moqUnit;
        form.product_variant_id.value = d.variant || '';
        const moqEl = document.getElementById('ms-qe-moq');
        if (moq) { moqEl.textContent = 'পাইকারি সর্বনিম্ন অর্ডার: ' + d.moq + ' ' + (UNIT_BN[moqUnit] || moqUnit); moqEl.classList.remove('hidden'); } else { moqEl.classList.add('hidden'); }
        // Remember the buyer on this device so the next enquiry is one tap.
        if (!form.customer_name.value) form.customer_name.value = read('ms_qe_name');
        if (!form.customer_phone.value) form.customer_phone.value = read('ms_qe_phone');
        if (!form.delivery_location.value) form.delivery_location.value = read('ms_qe_area');
        const biz = read('ms_qe_biz');
        if (biz) { const b = document.querySelector('#ms-qe-biz [data-biz="' + biz + '"]'); if (b) msQeBiz(b); }
        refreshRetailHint();

        err.classList.add('hidden');
        form.classList.remove('hidden'); done.classList.add('hidden');
        const socialBox = document.getElementById('ms-qe-social');
        if (socialBox) socialBox.classList.remove('hidden');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => (form.customer_name.value && form.customer_phone.value ? form.quantity_kg : form.customer_name).focus(), 50);
    };

    // Social sign-in from the popup: remember what they were asking about, then
    // reopen the popup for the same product once they are back (logged in).
    window.msQuickEnquirySocial = function () {
        if (lastData) {
            lastData.qty = form.quantity_kg.value;
            lastData.qunit = form.quantity_unit.value;
            store('ms_qe_pending', JSON.stringify(lastData));
        }
        return true;
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
        if (belowMoq()) { refreshRetailHint(); return showError('পাইকারি অর্ডারের জন্য কমপক্ষে ' + moq + ' ' + (UNIT_BN[moqUnit] || moqUnit) + ' দিন।'); }
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
            if (form.business_type.value) store('ms_qe_biz', form.business_type.value);

            const c = data.contact;
            if (c && slug) { const all = contacts(); all[slug] = { whatsapp: c.whatsapp, tel: c.tel, at: Date.now() }; store(CONTACT_KEY, JSON.stringify(all)); }

            const wa = document.getElementById('ms-qe-done-wa'), tel = document.getElementById('ms-qe-done-tel');
            wa.style.display = 'none'; tel.style.display = 'none';
            wa.textContent = 'WhatsApp-এ মেসেজ পাঠান';
            if (c) { wa.href = c.whatsapp; tel.href = c.tel; }
            const title = document.getElementById('ms-qe-done-title'), msg = document.getElementById('ms-qe-done-msg');
            if (mode === 'whatsapp' && c) {
                title.textContent = 'ধন্যবাদ! এখন WhatsApp-এ কথা বলুন';
                msg.textContent = 'আপনার তথ্যসহ মেসেজ লেখা আছে — শুধু Send চাপুন।';
                wa.style.display = 'flex';
                openContact(c, 'whatsapp');
            } else if (mode === 'call' && c) {
                title.textContent = 'ধন্যবাদ! এখন কল করুন';
                msg.textContent = 'কল করার সময় আপনার enquiry নম্বর #' + data.enquiry_id + ' বলুন।';
                tel.style.display = 'flex';
                openContact(c, 'call');
            } else {
                title.textContent = 'আপনার enquiry পাঠানো হয়েছে';
                msg.textContent = 'MoslaMart টিম শীঘ্রই আপনার নম্বরে দাম জানাবে।';
                if (c) { wa.textContent = 'আরও দ্রুত দাম পেতে WhatsApp করুন'; wa.style.display = 'flex'; }
            }
            form.classList.add('hidden'); done.classList.remove('hidden');
            const social = document.getElementById('ms-qe-social');
            if (social) social.classList.add('hidden');
            document.getElementById('ms-qe-lead').classList.add('hidden');
        } catch (ex) {
            showError('ইন্টারনেট সংযোগ দেখে আবার চেষ্টা করুন।');
        } finally {
            submitBtn.disabled = false; submitBtn.textContent = MODES[mode].submit;
        }
    });

    @auth
    // Back from Google/Facebook → reopen the popup that started the sign-in.
    (function () {
        const raw = read('ms_qe_pending');
        if (!raw) return;
        try { localStorage.removeItem('ms_qe_pending'); } catch (e) {}
        try { msQuickEnquiry({ dataset: JSON.parse(raw) }); } catch (e) {}
    })();
    @endauth
})();
</script>
