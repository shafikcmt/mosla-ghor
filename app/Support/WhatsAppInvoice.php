<?php

namespace App\Support;

use App\Models\Order;

/**
 * Builds the WhatsApp invoice message + wa.me share link from the
 * admin-controlled template. Only ever embeds internal MoslaMart links.
 */
class WhatsAppInvoice
{
    /** Fill the admin template with this order's details. */
    public static function messageFor(Order $order): string
    {
        $order->ensureTokens();

        $shopName = $order->createdByVendor?->shop_name ?? 'MoslaMart';

        $replacements = [
            '{customer_name}' => $order->customer_name,
            '{order_number}'  => $order->order_number,
            '{total}'         => number_format((float) $order->grand_total, 0),
            '{invoice_link}'  => $order->invoiceUrl() ?? '',
            '{reorder_link}'  => $order->reorderUrl() ?? '',
            '{shop_name}'     => $shopName,
        ];

        return strtr(VendorSettings::whatsappInvoiceTemplate(), $replacements);
    }

    /** Full https://wa.me link with the encoded message. */
    public static function linkFor(Order $order, ?string $phone = null): string
    {
        $phone = self::normalizePhone($phone
            ?? $order->vendorCustomer?->whatsappNumber()
            ?? $order->mobile_number);

        $text = rawurlencode(self::messageFor($order));

        return $phone
            ? "https://wa.me/{$phone}?text={$text}"
            : "https://wa.me/?text={$text}";
    }

    /** Convert a local 01XXXXXXXXX number to wa.me 88 format. */
    protected static function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '88' . $digits;
        } elseif (! str_starts_with($digits, '88')) {
            $digits = '88' . $digits;
        }
        return $digits;
    }
}
