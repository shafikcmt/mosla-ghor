<?php

namespace App\Notifications;

use App\Models\WholesaleQuote;
use Illuminate\Notifications\Notification;

class QuoteSubmittedNotification extends Notification
{
    /** @param string $audience 'customer'|'admin' */
    public function __construct(public WholesaleQuote $quote, public string $audience = 'customer')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $enquiryId = $this->quote->enquiry_id;

        [$title, $body, $route] = match ($this->audience) {
            'admin' => [
                'Supplier quote submit করেছে — monitor করুন',
                "Enquiry #{$enquiryId} — নতুন কোটেশন।",
                route('admin.wholesale.enquiry.show', $enquiryId),
            ],
            default => [
                'আপনার enquiry-তে নতুন quote এসেছে',
                "Enquiry #{$enquiryId} — quote দেখে order confirm করতে পারেন।",
                route('customer.wholesale.enquiry.show', $enquiryId),
            ],
        };

        return [
            'type'       => 'wholesale_quote',
            'title_bn'   => $title,
            'body_bn'    => $body,
            'url'        => $route,
            'icon'       => 'quote',
            'level'      => 'success',
            'enquiry_id' => $enquiryId,
            'quote_id'   => $this->quote->id,
        ];
    }
}
