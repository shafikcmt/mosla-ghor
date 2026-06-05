<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Notifications\Notification;

class ProductApprovedNotification extends Notification
{
    public function __construct(public Product $product)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $name = $this->product->name_bn ?: $this->product->name_en;

        return [
            'type'       => 'product_approved',
            'title_bn'   => 'পণ্য অনুমোদিত হয়েছে',
            'body_bn'    => "আপনার পণ্য \"{$name}\" অ্যাডমিন অনুমোদন করেছেন।",
            'url'        => route('vendor.products.edit', $this->product->id),
            'icon'       => 'check',
            'level'      => 'success',
            'product_id' => $this->product->id,
        ];
    }
}
