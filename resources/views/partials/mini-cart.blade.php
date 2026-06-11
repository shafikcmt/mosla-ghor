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

    {{-- Tabs --}}
    <div class="flex shrink-0 border-b border-green-100 bg-white">
        <button id="ms-tab-retail-btn" onclick="msCartTab('retail')"
                class="flex-1 px-3 py-2.5 text-sm font-semibold transition-colors flex items-center justify-center gap-1.5">
            খুচরা / কম্বো
            <span id="ms-tab-retail-count" class="bg-[#c9a227] text-[#0f3d22] text-[10px] font-bold rounded-full min-w-[18px] h-[18px] px-1 items-center justify-center" style="display:none;">0</span>
        </button>
        <button id="ms-tab-paykari-btn" onclick="msCartTab('paykari')"
                class="flex-1 px-3 py-2.5 text-sm font-semibold transition-colors flex items-center justify-center gap-1.5">
            পাইকারি
            <span id="ms-tab-paykari-count" class="bg-amber-700 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] px-1 items-center justify-center" style="display:none;">0</span>
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
        <div id="ms-bag-empty" class="flex-1 flex flex-col items-center justify-center text-center px-6 py-10" style="display:none;">
            <div class="text-5xl mb-3">🧺</div>
            <p class="font-serif-bn text-amber-800 text-lg font-bold">পাইকারি ব্যাগ খালি</p>
            <p class="text-gray-500 text-sm mt-1 mb-5">পাইকারি দরের জন্য পণ্য যোগ করুন</p>
        </div>
        <div id="ms-bag-list" class="flex-1 overflow-y-auto px-4 py-3 space-y-2"></div>
        <div id="ms-bag-foot" class="shrink-0 border-t border-amber-100 bg-white px-4 py-3" style="display:none;">
            <a href="{{ route('wholesale.enquiry-bag') }}"
               class="block w-full text-center bg-amber-700 hover:bg-amber-800 text-white font-bold text-sm py-3 rounded-xl transition shadow">
                পাইকারি ব্যাগ / দর জানতে চাই →
            </a>
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
    window.msBagSave = function (items) { try { localStorage.setItem(MS_BAG_KEY, JSON.stringify(items)); } catch (e) {} msBadges(); msCartRender(); };
    window.msBagCount = function () { return msBagGet().length; };
    window.msBagBadge = function () { msBadges(); };   // back-compat alias
    window.msBagAdd  = function (id, slug, name, image, qty, unit) {
        const items = msBagGet();
        const ex = items.find(function (x) { return x.product_id === id; });
        if (ex) { ex.quantity = (parseFloat(ex.quantity) || 0) + (parseFloat(qty) || 1); }
        else { items.push({ product_id: id, slug: slug, name: name, image: image, quantity: parseFloat(qty) || 1, unit: unit || 'kg' }); }
        msBagSave(items);
        msToast('🛍️ পাইকারি ব্যাগে যোগ হয়েছে');
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
    const TAB_ON_R  = 'flex-1 px-3 py-2.5 text-sm font-semibold flex items-center justify-center gap-1.5 bg-[#14532d] text-[#c9a227]';
    const TAB_OFF_R = 'flex-1 px-3 py-2.5 text-sm font-semibold flex items-center justify-center gap-1.5 text-gray-600 hover:bg-gray-50';
    const TAB_ON_P  = 'flex-1 px-3 py-2.5 text-sm font-semibold flex items-center justify-center gap-1.5 bg-amber-700 text-white';
    const TAB_OFF_P = 'flex-1 px-3 py-2.5 text-sm font-semibold flex items-center justify-center gap-1.5 text-gray-600 hover:bg-amber-50';

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
                bagList.innerHTML = bag.map(function (it) {
                    return '' +
                    '<div class="flex items-center gap-2 bg-white rounded-xl border border-amber-50 px-3 py-2.5 shadow-sm">' +
                        '<div class="flex-1 min-w-0">' +
                            '<div class="font-serif-bn text-amber-900 text-sm font-semibold truncate">' + (it.name || '') + '</div>' +
                            '<div class="text-[11px] text-gray-500 mt-0.5">পরিমাণ: ' + msFmt(it.quantity) + ' ' + (it.unit || '') + '</div>' +
                        '</div>' +
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
