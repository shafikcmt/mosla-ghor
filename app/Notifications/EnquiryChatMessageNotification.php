<?php

namespace App\Notifications;

use App\Models\WholesaleEnquiry;
use Illuminate\Notifications\Notification;

class EnquiryChatMessageNotification extends Notification
{
    /** @param string $audience 'admin'|'vendor'|'customer' */
    public function __construct(public WholesaleEnquiry $enquiry, public string $audience = 'admin')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $id = $this->enquiry->id;

        $route = match ($this->audience) {
            'vendor'   => route('vendor.wholesale.chat.show', $id),
            'customer' => route('customer.wholesale.chat.show', $id),
            default    => route('admin.wholesale.chat.show', $id),
        };

        return [
            'type'       => 'wholesale_chat',
            'title_bn'   => 'নতুন message এসেছে',
            'body_bn'    => "Enquiry #{$id} — নতুন বার্তা এসেছে।",
            'url'        => $route,
            'icon'       => 'chat',
            'level'      => 'info',
            'enquiry_id' => $id,
        ];
    }
}
