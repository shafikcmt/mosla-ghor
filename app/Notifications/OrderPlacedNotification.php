<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification
{
    public function __construct(public Order $order, public string $audience = 'vendor')
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Email admins only (they have real inboxes). TODO: extend to vendor/customer
        // once their User.email fields are reliably populated (currently phone-first).
        if ($this->audience === 'admin' && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $total = number_format((float) $this->order->grand_total, 0);
        $url   = $this->order->invoiceUrl();

        $mail = (new MailMessage)
            ->subject('নতুন অর্ডার — #' . $this->order->order_number)
            ->greeting('নতুন অর্ডার এসেছে')
            ->line("অর্ডার নম্বর: #{$this->order->order_number}")
            ->line("মোট: ৳{$total}");

        if ($url) {
            $mail->action('অর্ডার দেখুন', $url);
        }

        return $mail->line('MoslaMart Admin');
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
