<?php

namespace App\Notifications;

use App\Models\WholesaleQuote;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteSubmittedNotification extends Notification
{
    /** @param string $audience 'customer'|'admin' */
    public function __construct(public WholesaleQuote $quote, public string $audience = 'customer')
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Email admins only. TODO: extend to customer once their User.email is
        // reliably populated (customers currently sign in phone-first).
        if ($this->audience === 'admin' && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $enquiryId = $this->quote->enquiry_id;

        return (new MailMessage)
            ->subject("নতুন কোটেশন — Enquiry #{$enquiryId}")
            ->greeting('নতুন কোটেশন জমা হয়েছে')
            ->line("Enquiry #{$enquiryId} — একটি নতুন কোটেশন জমা হয়েছে।")
            ->action('কোটেশন দেখুন', route('admin.wholesale.quote.show', $this->quote->id))
            ->line('MoslaMart Admin');
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
