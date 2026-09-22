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

        // Admins: email when the account has an address.
        if ($this->audience === 'admin' && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        // Customers: email when the enquiry captured a contact address (guests
        // often have a null User.email but a filled enquiry customer_email).
        // The address is resolved via User::routeNotificationForMail() +
        // mailRouteOverride() below.
        if ($this->audience === 'customer' && $this->quote->enquiry?->hasEmailContact()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Preferred mail address for this notification. Laravel calls
     * routeNotificationForMail() on the notifiable (User); that method checks
     * for this override so we can deliver to a guest's captured enquiry email.
     */
    public function mailRouteOverride(): ?string
    {
        return $this->quote->enquiry?->contactEmail();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $enquiryId = $this->quote->enquiry_id;

        if ($this->audience === 'customer') {
            // Public, login-free quote link so guests without a password can open it.
            $this->quote->ensureInvoiceToken();

            $mail = (new MailMessage)
                ->theme('moslamart')
                ->subject("আপনার enquiry-তে নতুন কোটেশন — #{$enquiryId}")
                ->greeting('নতুন কোটেশন এসেছে')
                ->line("Enquiry #{$enquiryId} — আপনার চাহিদা অনুযায়ী একটি কোটেশন পাঠানো হয়েছে। সম্পূর্ণ ইনভয়েস PDF সংযুক্ত করা হলো।")
                ->action('কোটেশন দেখুন', $this->quote->invoiceUrl())
                ->line('MoslaMart Team');

            // Attach the real PDF invoice (same builder as the /pdf route). Never
            // let a PDF-build failure block the whole email.
            try {
                $mail->attachData(
                    \App\Support\WholesaleQuoteInvoicePdf::bytes($this->quote),
                    'invoice-' . $this->quote->id . '.pdf',
                    ['mime' => 'application/pdf'],
                );
            } catch (\Throwable) {
                // non-critical — send the email without the attachment
            }

            return $mail;
        }

        return (new MailMessage)
            ->theme('moslamart')
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
