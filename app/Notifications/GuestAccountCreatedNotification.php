<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mail-only. Sent once, right after a guest wholesale enquiry auto-creates a
 * trackable account, inviting the guest to set a password via a signed link.
 */
class GuestAccountCreatedNotification extends Notification
{
    public function __construct(public string $setPasswordUrl)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('MoslaMart — আপনার account তৈরি হয়েছে')
            ->greeting('স্বাগতম!')
            ->line('আপনার পাইকারি enquiry গ্রহণ করা হয়েছে। enquiry track করতে ও পরবর্তীতে সহজে login করতে নিচের বাটনে ক্লিক করে একটি password সেট করুন।')
            ->action('Password সেট করুন', $this->setPasswordUrl)
            ->line('এই লিংকটি ৭ দিনের জন্য বৈধ।')
            ->line('MoslaMart Team');
    }
}
