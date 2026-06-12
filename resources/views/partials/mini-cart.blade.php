{{-- ══════════════════════════════════════════════════════════════════════════
     Shared Mini-Cart drawer (retail combo/box  +  paykari enquiry bag)
     Included in home.blade.php and storefront/layout.blade.php so the SAME
     cart is reachable from a header icon on every page.

     • Owns window.msCart  → localStorage['ms_cart']  (retail box, persisted)
     • Owns the enquiry-bag helpers (msBagGet/Save/Add…) → localStorage['ms_enquiry_bag']
       (moved here out of storefront/layout so they exist on the home page too)
     • The retail item shape matches the home combo builder exactly:
         { uid, productId, priceId, variantId, variantName, sellType,
           quantity_gram, nameBn, label, price }

     Layout opt-outs (set before @include):
       $msHideFloat = true   → suppress the mobile floating button (home uses #combo-bar)
═══════════════════════════════════════════════════════════════════════════ --}}

<style>
    #ms-drawer { transform: translateX(100%); transition: transform .28s cubic-bezier(.4,0,.2,1); }
    #ms-drawer.open { transform: translateX(0); }
    #ms-drawer-backdrop { opacity: 0; transition: opacity .28s ease; }
    #ms-drawer-backdrop.open { opacity: 1; }
    #ms-cart-list::-webkit-scrollbar, #ms-bag-list::-webkit-scrollbar { width: 4px; }
    #ms-cart-list::-webkit-scrollbar-thumb, #ms-bag-list::-webkit-scrollbar-thumb { background:#c9a227; border-radius:3px; }
</style>

{{-- Backdrop --}}
<div id="ms-drawer-backdrop" onclick="msCartClose()"
     class="fixed inset-0 bg-black/50 z-[90] hidden" aria-hidden="true"></div>

{{-- Drawer panel --}}
<aside id="ms-drawer" role="dialog" aria-label="কার্ট"
       class="fixed top-0 right-0 h-full w-full max-w-sm bg-[#fef9ee] z-[100] shadow-2xl flex flex-col">

    {{-- Header --}}
    <div class="bg-[#0f3d22] px-4 py-3 flex items-center justify-between shrink-0">
        <span class="font-serif-bn text-[#c9a227] text-lg font-bold">আমার ব্যাগ</span>
        <button onclick="msCartClose()" aria-label="বন্ধ করুন"
                class="text-green-200 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Tabs (pinned at top of drawer; large tap targets, clear active state) --}}
    <div class="flex shrink-0 border-b-2 border-green-100 bg-white">
        <button id="ms-tab-retail-btn" onclick="msCartTab('retail')"
                class="flex-1 px-3 py-3.5 text-[15px] font-bold transition-colors flex items-center justify-center gap-1.5 border-b-2 border-transparent">
            খুচরা / কম্বো
            <span id="ms-tab-retail-count" class="bg-[#c9a227] text-[#0f3d22] text-[11px] font-bold rounded-full min-w-[20px] h-[20px] px-1 items-center justify-center" style="display:none;">0</span>
        </button>
        <button id="ms-tab-paykari-btn" onclick="msCartTab('paykari')"
                class="flex-1 px-3 py-3.5 text-[15px] font-bold transition-colors flex items-center justify-center gap-1.5 border-b-2 border-transparent">
            পাইকারি
            <span id="ms-tab-paykari-count" class="bg-amber-700 text-white text-[11px] font-bold rounded-full min-w-[20px] h-[20px] px-1 items-center justify-center" style="display:none;">0</span>
        </button>
    </div>

    {{-- ═══════════ RETAIL BODY ═══════════ --}}
    <div id="ms-retail-body" class="flex-1 flex flex-col min-h-0">
        {{-- Empty state --}}
        <div id="ms-cart-empty" class="flex-1 flex flex-col items-center justify-center text-center px-6 py-10" style="display:none;">
            <div class="text-5xl mb-3">🛍️</div>
            <p class="font-serif-bn text-[#14532d] text-lg font-bold">আপনার ব্যাগ এখনো খালি</p>
            <p class="text-gray-500 text-sm mt-1 mb-5">পণ্য যোগ করে সহজে অর্ডার করুন</p>
            <div class="flex flex-col gap-2 w-full max-w-[220px]">
                <a href="/#products" onclick="msCartClose()"
                   class="bg-[#14532d] text-white text-sm font-semibold py-2.5 rounded-xl hover:bg-[#166534] transition">পণ্য দেখুন</a>
                <a href="/#combo-builder" onclick="msCartClose()"
                   class="border border-[#c9a227] text-[#14532d] text-sm font-semibold py-2.5 rounded-xl hover:bg-amber-50 transition">কম্বো তৈরি করুন</a>
            </div>
        </div>

        {{-- Items --}}
        <div id="ms-cart-list" class="flex-1 overflow-y-auto px-4 py-3 space-y-2"></div>

        {{-- Footer: totals + actions --}}
        <div id="ms-cart-foot" class="shrink-0 border-t border-green-100 bg-white px-4 py-3 space-y-3" style="display:none;">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">সাবটোটাল</span>
                <span id="ms-cart-subtotal" class="font-serif-bn font-bold text-[#14532d] text-lg">৳০</span>
            </div>
            <p class="text-[11px] text-gray-400 leading-snug">ডেলিভারি চার্জ ও প্যাকেজিং চেকআউটে যুক্ত হবে।</p>
            <button onclick="msCartCheckout()"
                    class="w-full bg-[#c9a227] hover:bg-[#e2bb45] text-[#0f3d22] font-bold text-sm py-3 rounded-xl transition shadow">
                চেকআউট / অর্ডার করুন →
            </button>
            <div class="grid grid-cols-2 gap-2">
                <a href="/#combo-builder" onclick="msCartClose()"
                   class="text-center border border-[#14532d] text-[#14532d] text-xs font-semibold py-2 rounded-lg hover:bg-green-50 transition">+ কম্বো / বক্স</a>
                <a href="/#products" onclick="msCartClose()"
                   class="text-center border border-gray-300 text-gray-600 text-xs font-semibold py-2 rounded-lg hover:bg-gray-50 transition">শপিং চালিয়ে যান</a>
            </div>
        </div>
    </div>

    {{-- ═══════════ PAYKARI BODY ═══════════ --}}
    <div id="ms-paykari-body" class="flex-1 flex flex-col min-h-0" style="display:none;">
        {{-- Helper hint (shows whenever the wholesale tab is active) --}}
        <div class="shrink-0 bg-amber-50 border-b border-amber-100 px-4 py-2">
            <p class="text-[11px] text-amber-800 leading-snug">বাল্ক অর্ডারের জন্য kg, বস্তা বা কার্টন নির্বাচন করুন</p>
        </div>
        {{-- Empty state --}}
        <div id="ms-bag-empty" class="flex-1 flex flex-col items-center justify-center text-center px-6 py-10" style="display:none;">
            <div class="text-5xl mb-3">🧺</div>
            <p class="font-serif-bn text-amber-800 text-lg font-bold">আপনার পাইকারি ব্যাগ খালি</p>
            <p class="text-gray-500 text-sm mt-1 mb-5">বাল্ক অর্ডারের জন্য পণ্য যোগ করুন</p>
            <div class="flex flex-col gap-2 w-full max-w-[220px]">
                <a href="/?tab=wholesale#products" onclick="msCartClose()"
                   class="bg-amber-700 text-white text-sm font-semibold py-2.5 rounded-xl hover:bg-amber-800 transition">পাইকারি পণ্য দেখুন</a>
                <a href="/?combo=paykari#combo-builder"
                   class="border border-amber-600 text-amber-800 text-sm font-semibold py-2.5 rounded-xl hover:bg-amber-50 transition">পাইকারি কম্বো তৈরি করুন</a>
            </div>
        </div>

        {{-- Items --}}
        <div id="ms-bag-list" class="flex-1 overflow-y-auto px-4 py-3 space-y-2"></div>

        {{-- Footer: quick actions + main CTA --}}
        <div id="ms-bag-foot" class="shrink-0 border-t border-amber-100 bg-white px-4 py-3 space-y-3" style="display:none;">
            <p class="text-[11px] text-gray-400 leading-snug">পরিমাণ ঠিক করে দর জানতে চান — MoslaMart টিম quote পাঠাবে।</p>
            <a href="{{ route('wholesale.enquiry-bag') }}"
               class="block w-full text-center bg-amber-700 hover:bg-amber-800 text-white font-bold text-sm py-3 rounded-xl transition shadow">
                দর জানতে চাই →
            </a>
            <div class="grid grid-cols-2 gap-2">
                <a href="/?tab=wholesale#products" onclick="msCartClose()"
                   class="text-center border border-amber-700 text-amber-800 text-xs font-semibold py-2 rounded-lg hover:bg-amber-50 transition">+ আরও পাইকারি পণ্য</a>
                <a href="/?combo=paykari#combo-builder"
                   class="text-center border border-gray-300 text-gray-600 text-xs font-semibold py-2 rounded-lg hover:bg-gray-50 transition">পাইকারি কম্বো</a>
            </div>
        </div>
    </div>
</aside>

@unless(($msHideFloat ?? false))
{{-- Mobile floating cart button (hidden on home, which has its own #combo-bar) --}}
<button id="ms-float-cart" onclick="msCartOpen('retail')" aria-label="কার্ট"
        class="lg:hidden fixed bottom-5 right-5 z-[80] w-14 h-14 rounded-full bg-[#14532d] text-[#c9a227] shadow-2xl items-center justify-center" style="display:none;">
    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 4.6A1 1 0 005.6 19H19M9 22a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/></svg>
    <span data-cart-badge class="absolute -top-1 -right-1 bg-[#c9a227] text-[#0f3d22] text-[11px] font-bold rounded-full min-w-[20px] h-5 px-1 flex items-center justify-center" style="display:none;">0</span>
</button>
@endunless

<script>
(function () {
    // ── number format (shared; don't clobber home's fmt) ──────────────────
    function msFmt(v) { return Math.round(parseFloat(v) || 0).toLocaleString('bn-BD'); }

    // ── toast (reuse home's if present, else lightweight) ─────────────────
    if (typeof window.msToast !== 'function') {
        window.msToast = function (msg) {
            let t = document.getElementById('ms-toast');
            if (!t) {
                t = document.createElement('div');
                t.id = 'ms-toast';
                t.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-[200] hidden bg-[#14532d] text-white text-sm px-4 py-2.5 rounded-xl shadow-lg';
                document.body.appendChild(t);
            }
            t.textContent = msg; t.classList.remove('hidden');
            clearTimeout(window.__msToastT);
            window.__msToastT = setTimeout(function () { t.classList.add('hidden'); }, 2500);
        };
    }

    // ── Paykari enquiry bag store (moved here from storefront/layout) ──────
    // Exposed on window because the enquiry-bag page references MS_BAG_KEY globally.
    const MS_BAG_KEY = 'ms_enquiry_bag';
    window.MS_BAG_KEY = MS_BAG_KEY;
    window.msBagGet  = function () { try { return JSON.parse(localStorage.getItem(MS_BAG_KEY) || '[]'); } catch (e) { return []; } };
    window.msBagSave = function (items) {
        try { localStorage.setItem(MS_BAG_KEY, JSON.stringify(items)); } catch (e) {}
        msBadges(); msCartRender();
        // Notify other views of the same bag (e.g. the Paykari order builder) so
        // every surface stays in sync off this single source of truth.
        try { window.dispatchEvent(new CustomEvent('ms-bag-changed')); } catch (e) {}
    };
    window.msBagCount = function () { return msBagGet().length; };
    window.msBagBadge = function () { msBadges(); };   // back-compat alias
    window.msBagAdd  = function (id, slug, name, image, qty, unit, minQty, minUnit) {
        const items = msBagGet();
        const ex = items.find(function (x) { return x.product_id === id; });
        if (ex) {
            const cfg = msBagUnitCfg(ex.unit);
            ex.quantity = (parseFloat(ex.quantity) || 0) + (parseFloat(qty) || cfg.step);
        } else {
            const it = { product_id: id, slug: slug, name: name, image: image,
                         unit: unit || 'kg',
                         min_qty: parseFloat(minQty) || null, min_unit: minUnit || null };
            // Never start below the wholesale minimum for the chosen unit.
            it.quantity = Math.max(parseFloat(qty) || 0, msBagMin(it)) || msBagMin(it);
            items.push(it);
        }
        msBagSave(items);
        msToast('🛍️ পাইকারি ব্যাগে যোগ হয়েছে');
    };

    // ── Wholesale quantity rules (unit-aware step/min, honors product MOQ) ──
    // kg → step 5, default min 5;  বস্তা/কার্টন → step 1, min 1.
    function msBagUnitCfg(unit) {
        if (unit === 'bag')    return { step: 1, min: 1, chips: [1, 2, 5],   label: 'বস্তা', suffix: ' বস্তা' };
        if (unit === 'carton') return { step: 1, min: 1, chips: [1, 2, 5],   label: 'কার্টন', suffix: ' কার্টন' };
        return { step: 5, min: 5, chips: [5, 10, 25, 50], label: 'kg', suffix: 'kg' };   // kg default
    }
    // Product MOQ applies only in its own unit (or kg when unit unspecified).
    function msBagMin(it) {
        const cfg = msBagUnitCfg(it.unit);
        const pm = parseFloat(it.min_qty);
        if (pm > 0 && (it.min_unit ? it.min_unit === it.unit : it.unit === 'kg')) {
            return Math.max(cfg.min, pm);
        }
        return cfg.min;
    }
    function msBagWarn(it) {
        const cfg = msBagUnitCfg(it.unit);
        msToast('পাইকারি অর্ডারের জন্য কমপক্ষে ' + msFmt(msBagMin(it)) + cfg.suffix + ' নির্বাচন করুন');
    }
    // Exposed so other views (Paykari order builder) share the exact same rules.
    window.msBagUnitCfg = msBagUnitCfg;
    window.msBagMin     = msBagMin;

    // In-drawer management — msBagSave re-renders the drawer instantly.
    window.msBagInc = function (i) {
        const items = msBagGet(); if (!items[i]) return;
        items[i].quantity = (parseFloat(items[i].quantity) || 0) + msBagUnitCfg(items[i].unit).step;
        msBagSave(items);
    };
    window.msBagDec = function (i) {
        const items = msBagGet(); if (!items[i]) return;
        const min = msBagMin(items[i]);
        const next = (parseFloat(items[i].quantity) || 0) - msBagUnitCfg(items[i].unit).step;
        if (next < min) { items[i].quantity = min; msBagSave(items); msBagWarn(items[i]); return; }
        items[i].quantity = next; msBagSave(items);
    };
    window.msBagSetQty = function (i, val) {
        const items = msBagGet(); if (!items[i]) return;
        const min = msBagMin(items[i]);
        let q = parseFloat(val);
        if (!(q > 0) || q < min) { q = min; msBagWarn(items[i]); }
        items[i].quantity = q; msBagSave(items);
    };
    window.msBagSetUnit = function (i, unit) {
        const items = msBagGet(); if (!items[i]) return;
        items[i].unit = unit;
        items[i].quantity = msBagMin(items[i]);   // reset to that unit's minimum
        msBagSave(items);
    };
    window.msBagChip = function (i, val) {
        const items = msBagGet(); if (!items[i]) return;
        const min = msBagMin(items[i]);
        let q = parseFloat(val) || min;
        if (q < min) { q = min; msBagWarn(items[i]); }
        items[i].quantity = q; msBagSave(items);
    };
    window.msBagRemove = function (i) {
        const items = msBagGet();
        if (items[i]) { items.splice(i, 1); msBagSave(items); msToast('পণ্য সরানো হয়েছে'); }
    };

    // ── Retail cart store (localStorage['ms_cart']) ───────────────────────
    const MS_CART_KEY = 'ms_cart';
    window.msCart = {
        get() { try { return JSON.parse(localStorage.getItem(MS_CART_KEY) || '[]'); } catch (e) { return []; } },
        _write(items) { try { localStorage.setItem(MS_CART_KEY, JSON.stringify(items || [])); } catch (e) {} },
        // Persist + refresh badges/drawer. Does NOT call the home builder's
        // renderCombo (the builder calls THIS), so there is no render loop.
        replace(items) { this._write(items); msBadges(); msCartRender(); },
        save(items) { this.replace(items); },
        count() { return this.get().length; },
        subtotal() { return this.get().reduce(function (s, x) { return s + (parseFloat(x.price) || 0); }, 0); },
        // Add a fully-formed line (used by product-detail). Numeric uid keeps the
        // home builder's comboUid math intact when the item later appears there.
        add(item) {
            const items = this.get();
            const dup = items.find(function (x) {
                return x.productId === item.productId && x.priceId === item.priceId &&
                       (x.variantId || null) === (item.variantId || null);
            });
            if (dup) { this.replace(items); return dup.uid; }
            item.uid = Date.now() + Math.floor(Math.random() * 1000);
            items.push(item);
            this.replace(items);
            return item.uid;
        },
        remove(uid) {
            if (window.msComboBridge) { window.msComboBridge.remove(uid); }       // home: keep builder in sync
            else { this.replace(this.get().filter(function (x) { return String(x.uid) !== String(uid); })); }
        },
        clear() {
            if (window.msComboBridge) { window.msComboBridge.clear(); }
            else { this.replace([]); }
        }
    };

    // ── Badges (unified count on header icon + per-tab counts) ─────────────
    window.msBadges = function () {
        const c = msCart.count(), b = msBagGet().length, tot = c + b;
        document.querySelectorAll('[data-cart-badge]').forEach(function (el) {
            el.textContent = tot; el.style.display = tot > 0 ? 'flex' : 'none';
        });
        document.querySelectorAll('[data-bag-badge]').forEach(function (el) { // legacy paykari-only badge
            el.textContent = b; el.style.display = b > 0 ? 'flex' : 'none';
        });
        const rc = document.getElementById('ms-tab-retail-count');
        if (rc) { rc.textContent = c; rc.style.display = c > 0 ? 'inline-flex' : 'none'; }
        const pc = document.getElementById('ms-tab-paykari-count');
        if (pc) { pc.textContent = b; pc.style.display = b > 0 ? 'inline-flex' : 'none'; }
        const fb = document.getElementById('ms-float-cart');
        if (fb) fb.style.display = tot > 0 ? 'flex' : 'none';
    };

    // ── Drawer open / close / tab ─────────────────────────────────────────
    const TAB_ON_R  = 'flex-1 px-3 py-3.5 text-[15px] font-bold flex items-center justify-center gap-1.5 bg-[#14532d] text-[#c9a227] border-b-2 border-[#c9a227]';
    const TAB_OFF_R = 'flex-1 px-3 py-3.5 text-[15px] font-bold flex items-center justify-center gap-1.5 text-gray-500 hover:bg-gray-50 border-b-2 border-transparent';
    const TAB_ON_P  = 'flex-1 px-3 py-3.5 text-[15px] font-bold flex items-center justify-center gap-1.5 bg-amber-700 text-white border-b-2 border-amber-900';
    const TAB_OFF_P = 'flex-1 px-3 py-3.5 text-[15px] font-bold flex items-center justify-center gap-1.5 text-gray-500 hover:bg-amber-50 border-b-2 border-transparent';

    window.msCartTab = function (tab) {
        const retail  = document.getElementById('ms-retail-body');
        const paykari = document.getElementById('ms-paykari-body');
        const rb = document.getElementById('ms-tab-retail-btn');
        const pb = document.getElementById('ms-tab-paykari-btn');
        const isRetail = tab !== 'paykari';
        if (retail)  retail.style.display  = isRetail ? 'flex' : 'none';
        if (paykari) paykari.style.display = isRetail ? 'none' : 'flex';
        if (rb) rb.className = isRetail ? TAB_ON_R : TAB_OFF_R;
        if (pb) pb.className = isRetail ? TAB_OFF_P : TAB_ON_P;
    };

    window.msCartOpen = function (tab) {
        msCartRender();
        // Default to whichever bag has items; prefer the requested tab.
        const want = tab || (msCart.count() === 0 && msBagGet().length > 0 ? 'paykari' : 'retail');
        msCartTab(want);
        const bd = document.getElementById('ms-drawer-backdrop');
        const dr = document.getElementById('ms-drawer');
        if (bd) { bd.classList.remove('hidden'); requestAnimationFrame(function () { bd.classList.add('open'); }); }
        if (dr) requestAnimationFrame(function () { dr.classList.add('open'); });
        document.body.style.overflow = 'hidden';
    };

    window.msCartClose = function () {
        const bd = document.getElementById('ms-drawer-backdrop');
        const dr = document.getElementById('ms-drawer');
        if (dr) dr.classList.remove('open');
        if (bd) { bd.classList.remove('open'); setTimeout(function () { bd.classList.add('hidden'); }, 280); }
        document.body.style.overflow = '';
    };

    // ── Render drawer contents from the stores ────────────────────────────
    window.msCartRender = function () {
        // Retail
        const items = msCart.get();
        const list  = document.getElementById('ms-cart-list');
        const empty = document.getElementById('ms-cart-empty');
        const foot  = document.getElementById('ms-cart-foot');
        const subEl = document.getElementById('ms-cart-subtotal');
        if (list) {
            if (!items.length) {
                list.innerHTML = ''; list.style.display = 'none';
                if (empty) empty.style.display = 'flex';
                if (foot)  foot.style.display  = 'none';
            } else {
                list.style.display = 'block';
                if (empty) empty.style.display = 'none';
                if (foot)  foot.style.display  = 'block';
                list.innerHTML = items.map(function (it) {
                    const variant = it.variantName ? '<span class="text-gray-400"> · ' + it.variantName + '</span>' : '';
                    return '' +
                    '<div class="flex items-center gap-2 bg-white rounded-xl border border-green-50 px-3 py-2.5 shadow-sm">' +
                        '<div class="flex-1 min-w-0">' +
                            '<div class="font-serif-bn text-[#14532d] text-sm font-semibold truncate">' + (it.nameBn || '') + '</div>' +
                            '<div class="text-[11px] text-gray-500 mt-0.5">' + (it.label || '') + variant + '</div>' +
                        '</div>' +
                        '<div class="text-[#c9a227] font-serif-bn font-bold text-sm whitespace-nowrap">৳' + msFmt(it.price) + '</div>' +
                        '<button onclick="msCart.remove(\'' + it.uid + '\')" aria-label="মুছুন" ' +
                            'class="w-7 h-7 rounded-full bg-gray-100 hover:bg-red-100 text-gray-400 hover:text-red-500 flex items-center justify-center text-lg leading-none transition shrink-0">&times;</button>' +
                    '</div>';
                }).join('');
            }
        }
        if (subEl) subEl.textContent = '৳' + msFmt(msCart.subtotal());

        // Paykari (read-only view → links to the existing enquiry-bag page)
        const bag      = msBagGet();
        const bagList  = document.getElementById('ms-bag-list');
        const bagEmpty = document.getElementById('ms-bag-empty');
        const bagFoot  = document.getElementById('ms-bag-foot');
        if (bagList) {
            if (!bag.length) {
                bagList.innerHTML = ''; bagList.style.display = 'none';
                if (bagEmpty) bagEmpty.style.display = 'flex';
                if (bagFoot)  bagFoot.style.display  = 'none';
            } else {
                bagList.style.display = 'block';
                if (bagEmpty) bagEmpty.style.display = 'none';
                if (bagFoot)  bagFoot.style.display  = 'block';
                bagList.innerHTML = bag.map(function (it, i) {
                    const variant = it.variant ? '<span class="text-gray-400"> · ' + it.variant + '</span>' : '';
                    const cfg = msBagUnitCfg(it.unit);
                    const min = msBagMin(it);
                    const qty = parseFloat(it.quantity) || min;
                    // Unit selector (kg / বস্তা / কার্টন)
                    const units = [['kg', 'কেজি (kg)'], ['bag', 'বস্তা'], ['carton', 'কার্টন']];
                    const opts = units.map(function (u) {
                        return '<option value="' + u[0] + '"' + (it.unit === u[0] ? ' selected' : '') + '>' + u[1] + '</option>';
                    }).join('');
                    // Quick quantity chips for the current unit
                    const chips = cfg.chips.map(function (c) {
                        const on = qty === c;
                        return '<button type="button" onclick="msBagChip(' + i + ',' + c + ')" ' +
                            'class="px-2.5 py-1 rounded-full text-[11px] font-semibold border transition ' +
                            (on ? 'bg-amber-700 text-white border-amber-700' : 'border-amber-200 text-amber-800 hover:bg-amber-50') + '">' +
                            msFmt(c) + cfg.suffix + '</button>';
                    }).join('');
                    return '' +
                    '<div class="bg-white rounded-xl border border-amber-50 px-3 py-3 shadow-sm">' +
                        '<div class="flex items-start gap-2">' +
                            '<div class="flex-1 min-w-0">' +
                                '<div class="font-serif-bn text-amber-900 text-sm font-semibold truncate">' + (it.name || '') + variant + '</div>' +
                            '</div>' +
                            '<button onclick="msBagRemove(' + i + ')" aria-label="মুছুন" ' +
                                'class="w-7 h-7 rounded-full bg-gray-100 hover:bg-red-100 text-gray-400 hover:text-red-500 flex items-center justify-center text-lg leading-none transition shrink-0">&times;</button>' +
                        '</div>' +
                        '<div class="flex items-center gap-2 mt-2.5">' +
                            '<select onchange="msBagSetUnit(' + i + ',this.value)" aria-label="একক" ' +
                                'class="border border-amber-200 rounded-lg px-2 py-1.5 text-xs bg-white text-amber-900 font-medium focus:outline-none focus:ring-1 focus:ring-amber-500">' + opts + '</select>' +
                            '<div class="flex items-center gap-1 ml-auto">' +
                                '<button onclick="msBagDec(' + i + ')" aria-label="কমান" ' +
                                    'class="w-8 h-8 rounded-lg border border-amber-200 text-amber-800 font-bold text-lg leading-none flex items-center justify-center hover:bg-amber-50 transition">−</button>' +
                                '<input type="number" inputmode="decimal" min="' + min + '" step="' + cfg.step + '" value="' + qty + '" ' +
                                    'onchange="msBagSetQty(' + i + ',this.value)" aria-label="পরিমাণ" ' +
                                    'class="w-14 text-center border border-amber-200 rounded-lg px-1 py-1.5 text-sm font-bold text-amber-900 focus:outline-none focus:ring-1 focus:ring-amber-500">' +
                                '<button onclick="msBagInc(' + i + ')" aria-label="বাড়ান" ' +
                                    'class="w-8 h-8 rounded-lg border border-amber-200 text-amber-800 font-bold text-lg leading-none flex items-center justify-center hover:bg-amber-50 transition">+</button>' +
                            '</div>' +
                        '</div>' +
                        '<div class="flex flex-wrap gap-1.5 mt-2.5">' + chips + '</div>' +
                    '</div>';
                }).join('');
            }
        }
    };

    // ── Retail checkout: hand the box to the existing checkout.start flow ──
    window.msCartCheckout = function () {
        const items = msCart.get();
        if (!items.length) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = @json(route('checkout.start'));
        const csrf = document.createElement('input');
        csrf.type = 'hidden'; csrf.name = '_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
        form.appendChild(csrf);
        items.forEach(function (it, i) {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'items[' + i + ']'; inp.value = it.priceId;
            form.appendChild(inp);
        });
        document.body.appendChild(form);
        form.submit();
    };

    document.addEventListener('DOMContentLoaded', function () { msBadges(); msCartRender(); });
})();
</script>
