<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\OrderInvoicePdf;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mail-only. Sent once, right after a guest ORDER checkout auto-creates a
 * trackable account, confirming the order and inviting the guest to set a
 * password via a signed link. The real PDF invoice is attached.
 *
 * Mirrors {@see GuestAccountCreatedNotification} (wholesale enquiry) but carries
 * the order + its invoice. Only dispatched when an email was captured — the
 * order checkout has no email field today, so in practice the on-page WhatsApp
 * self-service button (order success page) is the primary claim path.
 */
class GuestOrderAccountCreatedNotification extends Notification
{
    public function __construct(public Order $order, public string $setPasswordUrl)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $total = number_format((float) $this->order->grand_total, 0);

        $mail = (new MailMessage)
            ->theme('moslamart')
            ->subject('MoslaMart — অর্ডার নিশ্চিত ও অ্যাকাউন্ট তৈরি হয়েছে')
            ->greeting('ধন্যবাদ আপনার অর্ডারের জন্য!')
            ->line("অর্ডার নম্বর: #{$this->order->order_number}")
            ->line("মোট: ৳{$total}")
            ->line('অর্ডার track করতে ও পরবর্তীতে সহজে login করতে নিচের বাটনে ক্লিক করে একটি password সেট করুন।')
            ->action('Password সেট করুন', $this->setPasswordUrl)
            ->line('এই লিংকটি ৭ দিনের জন্য বৈধ। সম্পূর্ণ PDF ইনভয়েস সংযুক্ত করা হলো।')
            ->line('MoslaMart Team');

        // Attach the real PDF invoice — never let a PDF-build failure block the email.
        try {
            $mail->attachData(
                OrderInvoicePdf::bytes($this->order),
                'invoice-' . $this->order->order_number . '.pdf',
                ['mime' => 'application/pdf'],
            );
        } catch (\Throwable) {
            // non-critical — send the email without the attachment
        }

        return $mail;
    }
}
