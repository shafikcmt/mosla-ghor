<?php

namespace App\Support;

use App\Models\Order;
use App\Models\WebsiteSetting;
use Mpdf\Mpdf;

/**
 * Single source of truth for building a regular Order's PDF invoice, shared by
 * the public /invoice/{token}/pdf route and the customer order email (so the
 * exact same PDF is produced in both places). Mirrors
 * {@see WholesaleQuoteInvoicePdf} and reuses the same verified mpdf config.
 *
 * Bengali rendering: mpdf's bundled FreeSerif (auto-selected for Bengali via
 * autoScriptToLang + autoLangToFont) has Bengali glyphs only in its REGULAR
 * face — its BOLD face renders tofu. So the template NEVER uses font-weight:bold
 * on Bengali; emphasis is font-size, colour, borders and background fills.
 */
class OrderInvoicePdf
{
    /** Render the order invoice and return the raw PDF bytes. */
    public static function bytes(Order $order): string
    {
        $order->loadMissing(['items', 'createdByVendor']);

        $html = view('invoice.pdf', [
            'order'    => $order,
            'vendor'   => $order->createdByVendor,
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
