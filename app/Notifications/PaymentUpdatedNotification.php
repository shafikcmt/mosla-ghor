<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class PaymentUpdatedNotification extends Notification
{
    public function __construct(public Order $order, public string $event = 'paid')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $titles = [
            'paid'    => 'পেমেন্ট সম্পন্ন হয়েছে',
            'partial' => 'আংশিক পেমেন্ট হয়েছে',
            'failed'  => 'পেমেন্ট ব্যর্থ হয়েছে',
        ];

        return [
            'type'     => 'payment_updated',
            'title_bn' => $titles[$this->event] ?? 'পেমেন্ট আপডেট',
            'body_bn'  => "Order #{$this->order->order_number} — ৳" . number_format((float) $this->order->grand_total, 0),
            'url'      => $this->order->invoiceUrl(),
            'icon'     => 'payment',
            'level'    => $this->event === 'failed' ? 'danger' : 'success',
            'order_id' => $this->order->id,
        ];
    }
}
