// MoslaMart Meta Pixel helper. Plain JS, no libraries.
// Every call is wrapped so a blocked or missing fbq (ad blockers) can never break the cart or checkout.
//
// Browser events handled here:
//   server-built queue  : ViewContent, InitiateCheckout, Purchase, Lead, CompleteRegistration, AddToWishlist
//   deferred            : AddPaymentInfo (sent by MoslaPixel.fire() when the customer submits the payment step)
//   AddToCart           : cart add / quantity increase (event id shared with the server copy)
//   Search              : settled product-search text (debounced / Enter)
//   Contact             : WhatsApp / phone link clicks (event id shared with the server copy)
(function () {
    var cfg = window.MoslaPixelConfig;
    if (!cfg) return;
    var initialised = {};
    var deferred = {};
    var fired = {};

    function hasFbq() { return typeof window.fbq === 'function'; }
    function round2(n) { return Math.round(n * 100) / 100; }
    function newId(prefix) {
        return prefix + '-' + Date.now().toString(36) + Math.random().toString(36).slice(2, 10);
    }

    // Vendor pixels are initialised lazily, only when this page actually sends them an event,
    // with Meta's automatic events off so they never collect other vendors' products.
    function initVendor(id) {
        if (initialised[id] || !hasFbq()) return;
        try {
            window.fbq('set', 'autoConfig', false, id);
            window.fbq('init', id);
            initialised[id] = true;
        } catch (e) {}
    }

    function track(pixel, name, params, eventId, vendor) {
        try {
            if (!hasFbq() || !/^\d{10,20}$/.test(String(pixel))) return;
            if (vendor) initVendor(pixel);
            if (eventId) window.fbq('trackSingle', String(pixel), name, params || {}, { eventID: String(eventId) });
            else window.fbq('trackSingle', String(pixel), name, params || {});
        } catch (e) {}
    }

    // Tell the server about an action it cannot observe itself (Conversions API copy, same event id).
    function serverCopy(url, payload) {
        try {
            if (!cfg.capi || !url || !window.fetch) return;
            var tokenEl = document.querySelector('meta[name="csrf-token"]');
            window.fetch(url, {
                method: 'POST', credentials: 'same-origin', keepalive: true,
                headers: {
                    'Content-Type': 'application/json', 'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': tokenEl ? tokenEl.getAttribute('content') : ''
                },
                body: JSON.stringify(payload)
            }).catch(function () {});
        } catch (e) {}
    }

    // Events held back until the customer acts (AddPaymentInfo). Fired at most once per page.
    function fire(name) {
        try {
            if (fired[name]) return;
            fired[name] = true;
            (deferred[name] || []).forEach(function (e) { track(e.pixel, e.name, e.params, e.eventID, !!e.vendor); });
        } catch (e) {}
    }

    window.MoslaPixel = { track: track, fire: fire };

    // 1) Server-built events for this page.
    try {
        (cfg.queue || []).forEach(function (e) {
            if (e.deferred) { (deferred[e.name] = deferred[e.name] || []).push(e); return; }
            track(e.pixel, e.name, e.params, e.eventID, !!e.vendor);
        });
    } catch (e) {}

    // 2) AddToCart: observe the shared cart store instead of touching the cart code.
    // qty = packs added by THIS action (a whole new line, or the increase of an existing line).
    function addToCart(item, qty) {
        var productId = parseInt(item.productId, 10);
        var priceId = parseInt(item.priceId, 10);
        qty = Math.max(1, parseInt(qty, 10) || 1);
        if (!productId) return;
        var price = parseFloat(item.price) || 0;
        var id = priceId ? productId + '-' + priceId : String(productId); // same id scheme as the server (MetaPixel::contentId)
        var vendorId = (cfg.productVendors || {})[productId] || null;
        var eventId = newId('atc');
        var params = {
            content_ids: [id], content_type: 'product',
            contents: [{ id: id, quantity: qty, item_price: price }],
            content_name: item.nameBn || undefined,
            num_items: qty,
            value: round2(price * qty), currency: cfg.currency || 'BDT'
        };
        // Scope 'own': platform gets AddToCart only for products the server confirmed as
        // admin-owned. Unknown owner = not sent (fail closed), never guessed as admin.
        var platformAllowed = !cfg.own || (!vendorId && (cfg.adminProducts || []).indexOf(productId) !== -1);
        if (platformAllowed) {
            (cfg.platform || []).forEach(function (pid) { track(pid, 'AddToCart', params, eventId); });
            if (priceId) serverCopy((cfg.endpoints || {}).cart, { event_id: eventId, price_id: priceId, qty: qty });
        }
        var vendorPixel = vendorId ? (cfg.vendors || {})[vendorId] : null;
        if (vendorPixel) track(vendorPixel, 'AddToCart', params, eventId + '-v' + vendorId, true);
    }

    function qtyOf(x) { return Math.max(1, parseInt(x && x.qty, 10) || 1); }

    function hookCart() {
        var cart = window.msCart;
        if (!cart || typeof cart.replace !== 'function' || cart.__moslaPixel) return;
        var original = cart.replace;
        cart.replace = function (items) {
            var before = null;
            try { before = {}; (this.get() || []).forEach(function (x) { before[String(x.uid)] = qtyOf(x); }); } catch (e) { before = null; }
            var result = original.apply(this, arguments); // the cart always runs first
            try {
                if (before) (items || []).forEach(function (x) {
                    if (!x || x.uid == null) return;
                    var prev = before[String(x.uid)];
                    if (prev === undefined) addToCart(x, qtyOf(x));          // new cart line
                    else if (qtyOf(x) > prev) addToCart(x, qtyOf(x) - prev);  // more packs of an existing line
                    // unchanged lines (page load / re-render) and decreases fire nothing
                });
            } catch (e) {}
            return result;
        };
        cart.__moslaPixel = true;
    }

    if (document.readyState === 'loading' && document.addEventListener) document.addEventListener('DOMContentLoaded', hookCart);
    else hookCart();

    // 3) Search: only a settled, non-empty query (1.5 s after typing stops, or Enter). Never per keystroke.
    function hookSearch() {
        var box = typeof document.getElementById === 'function' ? document.getElementById('hm-product-search') : null;
        if (!box || box.__moslaPixel) return;
        box.__moslaPixel = true;
        var timer = null, last = '';
        function send() {
            clearTimeout(timer);
            var q = (box.value || '').replace(/\s+/g, ' ').trim().slice(0, 100);
            if (q.length < 2 || q === last) return;
            last = q;
            (cfg.platform || []).forEach(function (pid) { track(pid, 'Search', { search_string: q }); });
        }
        box.addEventListener('input', function () {
            clearTimeout(timer);
            if (!(box.value || '').trim()) { last = ''; return; } // cleared: allow the same query again later
            timer = setTimeout(send, 1500);
        });
        box.addEventListener('keydown', function (e) { if (e.key === 'Enter') send(); });
    }
    if (document.readyState === 'loading' && document.addEventListener) document.addEventListener('DOMContentLoaded', hookSearch);
    else hookSearch();

    // 4) Contact: a click on a WhatsApp or phone link. Page views never fire it.
    var lastContact = { method: '', at: 0 };
    if (typeof document.addEventListener === 'function') document.addEventListener('click', function (ev) {
        try {
            var a = ev.target && ev.target.closest ? ev.target.closest('a[href]') : null;
            if (!a) return;
            var href = a.getAttribute('href') || '';
            var method = /^tel:/i.test(href) ? 'phone'
                : (/^https?:\/\/(wa\.me|api\.whatsapp\.com|wa\.link)\//i.test(href) ? 'whatsapp' : '');
            if (!method) return;
            var now = Date.now();
            if (lastContact.method === method && now - lastContact.at < 2000) return; // double click / bubbling
            lastContact = { method: method, at: now };
            var eventId = newId('ct');
            (cfg.platform || []).forEach(function (pid) { track(pid, 'Contact', { contact_method: method }, eventId); });
            serverCopy((cfg.endpoints || {}).contact, { event_id: eventId, method: method });
        } catch (e) {}
    }, true);
})();
