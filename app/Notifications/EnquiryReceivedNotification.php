<?php

namespace App\Notifications;

use App\Models\WholesaleEnquiry;
use Illuminate\Notifications\Notification;

class EnquiryReceivedNotification extends Notification
{
    /** @param string $audience 'admin'|'vendor'|'customer' */
    public function __construct(public WholesaleEnquiry $enquiry, public string $audience = 'admin')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $id      = $this->enquiry->id;
        $product = $this->enquiry->product_name;

        [$title, $body, $route] = match ($this->audience) {
            'vendor' => [
                'নতুন enquiry এসেছে — quote দিন',
                "Enquiry #{$id} — {$product}",
                route('vendor.wholesale.enquiry.show', $id),
            ],
            'customer' => [
                'আপনার enquiry গ্রহণ করা হয়েছে',
                "Enquiry #{$id} — Supplier/Admin quote দিলে আপনাকে জানানো হবে।",
                route('customer.wholesale.enquiry.show', $id),
            ],
            default => [
                'নতুন Paykari Enquiry এসেছে',
                "Enquiry #{$id} — {$product}",
                route('admin.wholesale.enquiry.show', $id),
            ],
        };

        return [
            'type'       => 'wholesale_enquiry',
            'title_bn'   => $title,
            'body_bn'    => $body,
            'url'        => $route,
            'icon'       => 'enquiry',
            'level'      => 'info',
            'enquiry_id' => $id,
        ];
    }
}
