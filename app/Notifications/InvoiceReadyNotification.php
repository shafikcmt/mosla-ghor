<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class InvoiceReadyNotification extends Notification
{
    public function __construct(public Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'invoice_ready',
            'title_bn' => 'আপনার invoice তৈরি হয়েছে',
            'body_bn'  => "Invoice #{$this->order->order_number} — Payment/Order details দেখতে link খুলুন।",
            'url'      => $this->order->invoiceUrl(),
            'icon'     => 'invoice',
            'level'    => 'info',
            'order_id' => $this->order->id,
        ];
    }
}
