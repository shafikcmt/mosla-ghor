<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class EnquiryOrderConfirmedNotification extends Notification
{
    /** @param string $audience 'admin'|'vendor'|'customer' */
    public function __construct(public Order $order, public string $audience = 'customer')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $number    = $this->order->order_number;
        $enquiryId = $this->order->enquiry_id;

        [$title, $route] = match ($this->audience) {
            'vendor'   => ['Enquiry order confirm হয়েছে', route('vendor.wholesale.enquiry.show', $enquiryId)],
            'admin'    => ['Enquiry order confirm হয়েছে', route('admin.wholesale.enquiry.show', $enquiryId)],
            default    => ['আপনার order confirm হয়েছে', route('customer.wholesale.enquiry.show', $enquiryId)],
        };

        return [
            'type'       => 'wholesale_order',
            'title_bn'   => $title,
            'body_bn'    => "Order #{$number} তৈরি হয়েছে।",
            'url'        => $route,
            'icon'       => 'order',
            'level'      => 'success',
            'enquiry_id' => $enquiryId,
            'order_id'   => $this->order->id,
        ];
    }
}
