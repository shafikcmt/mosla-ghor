// MoslaMart Meta Pixel helper. Plain JS, no libraries.
// Every call is wrapped so a blocked or missing fbq (ad blockers) can never break the cart or checkout.
(function () {
    var cfg = window.MoslaPixelConfig;
    if (!cfg) return;
    var initialised = {};

    function hasFbq() { return typeof window.fbq === 'function'; }

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

    window.MoslaPixel = { track: track };

    // 1) Server-built events for this page (ViewContent, InitiateCheckout, Purchase, Lead, …).
    try {
        (cfg.queue || []).forEach(function (e) { track(e.pixel, e.name, e.params, e.eventID, !!e.vendor); });
    } catch (e) {}

    // 2) AddToCart: observe the shared cart store instead of touching the cart code.
    function addToCart(item) {
        var productId = parseInt(item.productId, 10);
        var priceId = parseInt(item.priceId, 10);
        if (!productId) return;
        var price = parseFloat(item.price) || 0;
        var id = priceId ? productId + '-' + priceId : String(productId);
        var vendorId = (cfg.productVendors || {})[productId] || null;
        var params = {
            content_ids: [id], content_type: 'product',
            contents: [{ id: id, quantity: 1, item_price: price }],
            content_name: item.nameBn || undefined,
            value: price, currency: cfg.currency || 'BDT'
        };
        if (!(cfg.own && vendorId)) {
            (cfg.platform || []).forEach(function (pid) { track(pid, 'AddToCart', params); });
        }
        var vendorPixel = vendorId ? (cfg.vendors || {})[vendorId] : null;
        if (vendorPixel) track(vendorPixel, 'AddToCart', params, null, true);
    }

    function hookCart() {
        var cart = window.msCart;
        if (!cart || typeof cart.replace !== 'function' || cart.__moslaPixel) return;
        var original = cart.replace;
        cart.replace = function (items) {
            var before = null;
            try { before = {}; (this.get() || []).forEach(function (x) { before[String(x.uid)] = true; }); } catch (e) { before = null; }
            var result = original.apply(this, arguments); // the cart always runs first
            try {
                if (before) (items || []).forEach(function (x) { if (x && x.uid != null && !before[String(x.uid)]) addToCart(x); });
            } catch (e) {}
            return result;
        };
        cart.__moslaPixel = true;
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', hookCart);
    else hookCart();
})();
