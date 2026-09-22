<?php

namespace App\Support;

use App\Models\WebsiteSetting;

/**
 * Typed accessors over the WebsiteSetting key/value store for every
 * admin-controlled vendor feature toggle. Used by controllers AND blades so
 * gating stays consistent everywhere.
 */
class VendorSettings
{
    // WhatsApp renders *bold*/_italic_ client-side, so it is safe here (the
    // no-bold-Bengali rule only applies to the mpdf PDF invoice, not WhatsApp).
    public const DEFAULT_WHATSAPP_TEMPLATE = "🧾 *MoslaMart ইনভয়েস*\n━━━━━━━━━━━━━━━\nAssalamu Alaikum {customer_name},\nআপনার order confirm হয়েছে ✅\n\n📦 Order: *#{order_number}*\n💰 Total: *৳{total}*\n━━━━━━━━━━━━━━━\n🧾 PDF ইনভয়েস ডাউনলোড:\n{invoice_pdf_link}\n\n🔗 Invoice / Payment:\n{invoice_link}\n\n🔄 আবার order করতে:\n{reorder_link}\n━━━━━━━━━━━━━━━\nধন্যবাদ 🙏\n_{shop_name} — MoslaMart_";

    /** Boolean toggle with a default. */
    protected static function bool(string $key, bool $default): bool
    {
        $val = WebsiteSetting::get($key, $default ? '1' : '0');
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    public static function vendorCanAddProduct(): bool      { return self::bool('vendor_can_add_product', true); }
    public static function vendorCanManageStock(): bool     { return self::bool('vendor_can_manage_stock', true); }
    public static function vendorCanCreateCustomer(): bool  { return self::bool('vendor_can_create_customer', true); }
    public static function vendorCanCreateOrder(): bool     { return self::bool('vendor_can_create_order', true); }
    public static function vendorCanShareWhatsapp(): bool   { return self::bool('vendor_can_share_whatsapp', true); }
    public static function vendorCanGiveDiscount(): bool    { return self::bool('vendor_can_give_discount', true); }
    public static function vendorCanAllowDue(): bool        { return self::bool('vendor_can_allow_due', true); }
    public static function stockNegativeAllowed(): bool     { return self::bool('stock_negative_allowed', false); }

    /** Reuses the existing key created by the original multivendor work. */
    public static function vendorProductAutoApprove(): bool { return self::bool('vendor_product_auto_approve', false); }

    public static function vendorMaxDiscountPercent(): float
    {
        return (float) WebsiteSetting::get('vendor_max_discount_percent', '100');
    }

    public static function invoiceTokenExpiryDays(): int
    {
        return (int) WebsiteSetting::get('invoice_token_expiry_days', '0');
    }

    public static function whatsappInvoiceTemplate(): string
    {
        $tpl = WebsiteSetting::get('whatsapp_invoice_template', '');
        return trim($tpl) !== '' ? $tpl : self::DEFAULT_WHATSAPP_TEMPLATE;
    }

    /** All keys managed by the admin settings form (for bulk save). */
    public static function boolKeys(): array
    {
        return [
            'vendor_can_add_product',
            'vendor_can_manage_stock',
            'vendor_can_create_customer',
            'vendor_can_create_order',
            'vendor_can_share_whatsapp',
            'vendor_can_give_discount',
            'vendor_can_allow_due',
            'stock_negative_allowed',
            'vendor_product_auto_approve',
        ];
    }
}
