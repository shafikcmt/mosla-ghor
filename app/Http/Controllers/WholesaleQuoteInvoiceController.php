<?php

namespace App\Http\Controllers;

use App\Models\WebsiteSetting;
use App\Models\WholesaleQuote;

/**
 * Public, token-addressed wholesale quote invoice.
 * No auth — the secret token IS the access control, mirroring InvoiceController
 * for orders. Lets a guest who never set a password open their quote.
 */
class WholesaleQuoteInvoiceController extends Controller
{
    /** Resolve a quote by its public invoice token or 404. */
    private function resolve(string $token): WholesaleQuote
    {
        $quote = WholesaleQuote::where('invoice_token', $token)->first();
        if (! $quote) {
            abort(404);
        }
        return $quote;
    }

    public function show(string $token)
    {
        $quote = $this->resolve($token);
        $quote->load(['enquiry', 'vendor']);

        return view('wholesale-invoice.show', [
            'quote'    => $quote,
            'vendor'   => $quote->vendor,
            'siteName' => WebsiteSetting::get('site_name', 'মসলা মার্ট'),
        ]);
    }
}
