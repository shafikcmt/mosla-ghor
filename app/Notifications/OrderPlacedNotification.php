<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification
{
    public function __construct(public Order $order, public string $audience = 'vendor')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isAdmin = $this->audience === 'admin';
        $shop    = $this->order->createdByVendor?->shop_name;

        return [
            'type'     => 'order_placed',
            'title_bn' => $isAdmin ? 'নতুন অর্ডার তৈরি হয়েছে' : 'নতুন অর্ডার এসেছে',
            'body_bn'  => $isAdmin && $shop
                ? "ভেন্ডর {$shop} একটি নতুন order তৈরি করেছে: #{$this->order->order_number}"
                : "নতুন অর্ডার: #{$this->order->order_number} — ৳" . number_format((float) $this->order->grand_total, 0),
            'url'      => $this->order->invoiceUrl(),
            'icon'     => 'order',
            'level'    => 'info',
            'order_id' => $this->order->id,
        ];
    }
}
