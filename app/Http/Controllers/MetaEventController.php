<?php

namespace App\Http\Controllers;

use App\Models\ProductPrice;
use App\Services\CheckoutService;
use App\Support\MetaCapi;
use App\Support\MetaPixel;
use Illuminate\Http\Request;

/**
 * Server copies (Conversions API) of two browser-only actions: AddToCart (the cart lives in
 * localStorage) and Contact (WhatsApp / phone click). The browser supplies only ids it already
 * generated; every price, product and vendor decision is re-derived here, so a forged request
 * cannot push arbitrary values into the dataset. Always answers 204 — tracking never blocks the UI.
 */
class MetaEventController extends Controller
{
    private const EVENT_ID = ['required', 'string', 'regex:/^[a-z]{2,4}-[a-z0-9]{8,40}$/'];

    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'event_id' => self::EVENT_ID,
            'price_id' => ['required', 'integer', 'min:1'],
            'qty' => ['required', 'integer', 'min:1', 'max:'.CheckoutService::MAX_PACKS_PER_LINE],
        ]);

        $price = ProductPrice::query()->active()->with('product')->find($data['price_id']);
        if ($price && $price->product && $price->product->is_active && MetaPixel::active() && MetaCapi::enabled(true)) {
            $line = MetaPixel::lineForPack($price, (int) $data['qty']);
            if ($line && MetaPixel::platformAllowsProduct($line['vendor_id'])) {
                MetaCapi::cartEvent('AddToCart', $data['event_id'], MetaPixel::cartParamsFor([$line]));
            }
        }

        return response()->noContent();
    }

    public function contact(Request $request)
    {
        $data = $request->validate([
            'event_id' => self::EVENT_ID,
            'method' => ['required', 'in:whatsapp,phone'],
        ]);

        if (MetaPixel::active()) {
            MetaCapi::contact($data['method'], $data['event_id']);
        }

        return response()->noContent();
    }
}
