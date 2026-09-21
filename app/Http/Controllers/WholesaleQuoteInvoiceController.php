<?php

namespace App\Http\Controllers;

use App\Models\WebsiteSetting;
use App\Models\WholesaleQuote;
use App\Support\WholesaleQuoteInvoicePdf;

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

    /** Real (server-generated) PDF of the quote — same data as show(). */
    public function pdf(string $token)
    {
        $quote = $this->resolve($token);

        $pdf = WholesaleQuoteInvoicePdf::bytes($quote);

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="quote-' . $quote->id . '-invoice.pdf"');
    }
}
