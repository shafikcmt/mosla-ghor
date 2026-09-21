<?php

namespace App\Support;

use App\Models\WebsiteSetting;
use App\Models\WholesaleQuote;
use Mpdf\Mpdf;

/**
 * Single source of truth for building the wholesale quote PDF invoice, shared by
 * the public /pdf route and the customer quote email (so the exact same PDF is
 * produced in both places).
 *
 * Bengali rendering: mpdf v8's bundled font set has no dedicated Bengali font,
 * but autoScriptToLang + autoLangToFont routes Bengali to the bundled FreeSerif,
 * whose OpenType shaping renders যুক্তাক্ষর (conjuncts) correctly. Verified by
 * rasterising a sample PDF and visually inspecting the glyphs. Do NOT switch to
 * a 'default_font' that isn't in the bundle — mpdf will error.
 */
class WholesaleQuoteInvoicePdf
{
    /** Render the quote invoice and return the raw PDF bytes. */
    public static function bytes(WholesaleQuote $quote): string
    {
        $quote->loadMissing(['enquiry', 'vendor']);

        $html = view('wholesale-invoice.pdf', [
            'quote'    => $quote,
            'vendor'   => $quote->vendor,
            'siteName' => WebsiteSetting::get('site_name', 'মসলা মার্ট'),
        ])->render();

        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'tempDir'          => $tempDir,
            'margin_left'      => 12,
            'margin_right'     => 12,
            'margin_top'       => 14,
            'margin_bottom'    => 14,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
