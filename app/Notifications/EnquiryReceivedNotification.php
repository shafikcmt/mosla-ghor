<?php

namespace App\Notifications;

use App\Models\WholesaleEnquiry;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnquiryReceivedNotification extends Notification
{
    /** @param string $audience 'admin'|'vendor'|'customer' */
    public function __construct(public WholesaleEnquiry $enquiry, public string $audience = 'admin')
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Email admins only. TODO: extend to vendor/customer once their User.email
        // fields are reliably populated (currently phone-first).
        if ($this->audience === 'admin' && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $id      = $this->enquiry->id;
        $product = $this->enquiry->productLabel();

        $e   = $this->enquiry;
        $qty = rtrim(rtrim(number_format((float) $e->quantity_kg, 2, '.', ''), '0'), '.').' '.($e->quantity_unit ?: 'kg');

        return (new MailMessage)
            ->subject("নতুন পাইকারি Enquiry #{$id} — {$product} ({$qty})")
            ->greeting('নতুন পাইকারি Enquiry এসেছে 🛒')
            ->line("Enquiry #{$id} — দ্রুত কোটেশন দিলে অর্ডার পাওয়ার সম্ভাবনা বাড়ে।")
            ->line(\App\Support\MailDetails::table([
                'পণ্য'          => $product,
                'পরিমাণ'        => $qty,
                'ক্রেতা'         => $e->customer_name,
                'মোবাইল'        => $e->customer_phone,
                'ইমেইল'         => $e->customer_email,
                'ব্যবসার ধরন'    => $e->business_type && $e->business_type !== 'other' ? $e->businessTypeLabel() : null,
                'এলাকা'         => $e->delivery_location,
                'যোগাযোগ'       => \App\Models\WholesaleEnquiry::CHANNELS[$e->contact_channel ?? 'form'] ?? null,
                'বার্তা'          => $e->message,
            ]))
            ->action('Enquiry দেখুন ও কোটেশন দিন', route('admin.wholesale.enquiry.show', $id));
    }

    public function toArray(object $notifiable): array
    {
        $id      = $this->enquiry->id;
        $product = $this->enquiry->productLabel();

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
