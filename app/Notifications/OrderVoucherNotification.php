<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\MailDetails;
use App\Support\OrderInvoicePdf;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Voucher for an order the admin took by phone / WhatsApp: summary table, PDF
 * attached, invoice link and — for a customer without a password yet — the
 * set-password (registration) link.
 */
class OrderVoucherNotification extends Notification
{
    public function __construct(public Order $order, public ?string $setPasswordUrl = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $o = $this->order;

        $mail = (new MailMessage)
            ->subject('আপনার অর্ডার #'.$o->order_number.' — ভাউচার')
            ->greeting('ধন্যবাদ, '.$o->customer_name.'!')
            ->line('আপনার অর্ডারটি গ্রহণ করা হয়েছে। বিস্তারিত নিচে, সম্পূর্ণ ভাউচার PDF সংযুক্ত।')
            ->line(MailDetails::table([
                'অর্ডার নম্বর'  => '#'.$o->order_number,
                'পণ্য'         => $o->items->map(fn ($i) => $i->product_name)->implode(', '),
                'মোট'          => '৳'.number_format((float) $o->grand_total, 2),
                'পরিশোধিত'     => (float) $o->paid_amount > 0 ? '৳'.number_format((float) $o->paid_amount, 2) : null,
                'বাকি'          => (float) $o->due_amount > 0 ? '৳'.number_format((float) $o->due_amount, 2) : null,
            ]))
            ->action('ভাউচার দেখুন', $o->invoiceUrl());

        if ($this->setPasswordUrl) {
            $mail->line('পরের অর্ডার সহজে করতে ও অর্ডার ট্র্যাক করতে একটি password সেট করুন (৭ দিন বৈধ):')
                ->line('[অ্যাকাউন্ট চালু করুন →]('.$this->setPasswordUrl.')');
        }

        try {
            $mail->attachData(OrderInvoicePdf::bytes($o), 'voucher-'.$o->order_number.'.pdf', ['mime' => 'application/pdf']);
        } catch (\Throwable) {
            // non-critical — send without the attachment
        }

        return $mail;
    }
}
