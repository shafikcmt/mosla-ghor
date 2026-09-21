<?php

namespace App\Support;

use App\Models\WholesaleEnquiry;
use App\Models\WholesaleQuote;

/**
 * Builds click-to-send wa.me links for the guest-account / quote reply flow.
 *
 * Nothing is ever sent from the server — an admin clicks the link and WhatsApp
 * opens with a pre-filled message. Only internal MoslaMart links are embedded,
 * matching the WhatsAppInvoice pattern. Reuses Phone::toWa() for number format.
 */
class WhatsAppGuestAccount
{
    /**
     * "Set your password to track this enquiry" WhatsApp link. Regenerates a
     * fresh signed set-password URL each time. Null when the enquiry has no
     * login account or no usable WhatsApp/phone number.
     */
    public static function linkFor(WholesaleEnquiry $enquiry): ?string
    {
        $user = $enquiry->customer?->user;
        if (! $user) {
            return null;
        }

        $wa = Phone::toWa($enquiry->whatsappNumber());
        if (! $wa) {
            return null;
        }

        $setUrl = GuestWholesaleAccount::setPasswordUrlFor($user);

        $message = "আসসালামু আলাইকুম {$enquiry->customer_name},\n\n"
            . "MoslaMart-এ আপনার enquiry (#{$enquiry->id} — {$enquiry->productLabel()}) গ্রহণ করা হয়েছে।\n"
            . "নিচের লিংকে password সেট করে enquiry track করুন:\n{$setUrl}\n\n"
            . "— MoslaMart";

        return 'https://wa.me/' . $wa . '?text=' . rawurlencode($message);
    }

    /**
     * WhatsApp link carrying a quote's price + total for the customer. Null when
     * the enquiry has no usable WhatsApp/phone number.
     */
    public static function replyLinkFor(WholesaleQuote $quote): ?string
    {
        $enquiry = $quote->enquiry;
        if (! $enquiry) {
            return null;
        }

        $wa = Phone::toWa($enquiry->whatsappNumber());
        if (! $wa) {
            return null;
        }

        $unit  = number_format((float) $quote->unit_price, 2);
        $total = number_format($quote->grandTotal(), 2);

        // Public, login-free PDF invoice — a guest without a password can open it.
        $quote->ensureInvoiceToken();
        $pdfUrl = $quote->invoicePdfUrl();

        $message = "আসসালামু আলাইকুম {$enquiry->customer_name},\n\n"
            . "আপনার enquiry (#{$enquiry->id} — {$enquiry->productLabel()}) এর কোটেশন:\n"
            . "ইউনিট মূল্য: ৳{$unit}/{$quote->quantity_unit}\n"
            . "মোট: ৳{$total}\n\n"
            . "সম্পূর্ণ PDF ইনভয়েস ডাউনলোড করুন:\n{$pdfUrl}\n\n"
            . "— MoslaMart";

        return 'https://wa.me/' . $wa . '?text=' . rawurlencode($message);
    }
}
