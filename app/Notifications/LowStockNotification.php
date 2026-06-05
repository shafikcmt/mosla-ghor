<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
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
        $name   = $this->product->name_bn ?: $this->product->name_en;
        $onHand = rtrim(rtrim(number_format($this->product->onHand(), 3, '.', ''), '0'), '.');
        $out    = $this->product->stockStatus() === 'out_of_stock';

        return [
            'type'       => 'low_stock',
            'title_bn'   => $out ? 'স্টক শেষ' : 'স্টক কম',
            'body_bn'    => "{$name} — বর্তমান স্টক {$onHand} {$this->product->stockUnit()}",
            'url'        => null,
            'icon'       => 'stock',
            'level'      => $out ? 'danger' : 'warning',
            'product_id' => $this->product->id,
        ];
    }
}
