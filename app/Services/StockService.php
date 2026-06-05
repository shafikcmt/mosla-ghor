<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorStockMovement;
use App\Notifications\LowStockNotification;
use App\Support\VendorSettings;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for product on-hand changes + the movement ledger.
 *
 * Legacy spice products store on-hand as a whole-kg integer (`products.stock`);
 * vendor unit-managed products use `products.stock_qty`. `Product::onHand()` /
 * `applyOnHand()` abstract that away so this service treats both uniformly.
 */
class StockService
{
    /**
     * Apply a signed delta to a product's on-hand and append a ledger row.
     * Runs in a row-locked transaction to avoid races.
     *
     * @param  array{reference_type?:?string,reference_id?:?int,note?:?string,created_by?:?int,allow_negative?:bool}  $opts
     */
    public function record(Product $product, string $type, float $delta, array $opts = []): VendorStockMovement
    {
        $allowNegative = ($opts['allow_negative'] ?? false) || VendorSettings::stockNegativeAllowed();

        $movement = DB::transaction(function () use ($product, $type, $delta, $opts, $allowNegative) {
            /** @var Product $p */
            $p    = Product::lockForUpdate()->findOrFail($product->id);
            $prev = $p->onHand();
            $new  = $prev + $delta;

            if ($new < 0 && ! $allowNegative) {
                throw new \RuntimeException('দুঃখিত, ' . ($p->name_bn ?: 'পণ্যটি') . '-এর পর্যাপ্ত স্টক নেই।');
            }

            $p->applyOnHand($new);   // clamps stored value to >= 0
            $p->save();

            $actual = $p->onHand();  // post-clamp value actually stored

            return VendorStockMovement::create([
                'vendor_id'          => $p->vendor_id,
                'product_id'         => $p->id,
                'product_variant_id' => null,
                'type'               => $type,
                'quantity'           => $delta,
                'previous_stock'     => $prev,
                'new_stock'          => $actual,
                'reference_type'     => $opts['reference_type'] ?? null,
                'reference_id'       => $opts['reference_id'] ?? null,
                'note'               => $opts['note'] ?? null,
                'created_by'         => $opts['created_by'] ?? null,
            ]);
        });

        $this->maybeNotifyLowStock($product->fresh(), $delta);

        return $movement;
    }

    public function add(Product $product, float $qty, array $opts = []): VendorStockMovement
    {
        return $this->record($product, 'add', abs($qty), $opts);
    }

    public function reduce(Product $product, float $qty, array $opts = []): VendorStockMovement
    {
        return $this->record($product, 'reduce', -abs($qty), $opts);
    }

    /** Set on-hand to an absolute target (logs the delta as an adjustment). */
    public function adjust(Product $product, float $target, array $opts = []): VendorStockMovement
    {
        $delta = $target - $product->onHand();
        return $this->record($product, 'adjustment', $delta, $opts);
    }

    /** Deduct stock for every line of a (POS/vendor) order. */
    public function deductForOrder(Order $order, ?int $by = null): void
    {
        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }
            $product = Product::find($item->product_id);
            if (! $product) {
                continue;
            }
            $qty = $this->itemQuantity($item);
            if ($qty <= 0) {
                continue;
            }
            $this->record($product, 'order', -$qty, [
                'reference_type' => 'order',
                'reference_id'   => $order->id,
                'note'           => 'অর্ডার #' . $order->order_number,
                'created_by'     => $by,
            ]);
        }
    }

    /** Restore stock for every line of an order (cancel/return). */
    public function restoreForOrder(Order $order, string $type = 'cancel', ?int $by = null): void
    {
        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }
            $product = Product::find($item->product_id);
            if (! $product) {
                continue;
            }
            $qty = $this->itemQuantity($item);
            if ($qty <= 0) {
                continue;
            }
            $this->record($product, $type, $qty, [
                'reference_type' => 'order',
                'reference_id'   => $order->id,
                'note'           => ($type === 'return' ? 'ফেরত' : 'বাতিল') . ' #' . $order->order_number,
                'created_by'     => $by,
                'allow_negative' => true,
            ]);
        }
    }

    /**
     * Append a ledger row WITHOUT changing stock — used by the public checkout,
     * which deducts inline and only needs the movement recorded for history.
     */
    public function logOnly(Product $product, string $type, float $delta, float $previous, float $new, array $opts = []): void
    {
        VendorStockMovement::create([
            'vendor_id'          => $product->vendor_id,
            'product_id'         => $product->id,
            'product_variant_id' => null,
            'type'               => $type,
            'quantity'           => $delta,
            'previous_stock'     => $previous,
            'new_stock'          => $new,
            'reference_type'     => $opts['reference_type'] ?? null,
            'reference_id'       => $opts['reference_id'] ?? null,
            'note'               => $opts['note'] ?? null,
            'created_by'         => $opts['created_by'] ?? null,
        ]);
    }

    /** Quantity to move for an order line, in the product's own unit. */
    public function itemQuantity($item): float
    {
        if ($item->quantity !== null) {
            return (float) $item->quantity;
        }
        if ($item->quantity_gram) {
            return (float) ceil($item->quantity_gram / 1000); // legacy: whole kg
        }
        return 0.0;
    }

    /** Fire a low-stock notification to the vendor + admins after a reduction. */
    protected function maybeNotifyLowStock(?Product $product, float $delta): void
    {
        if (! $product || $delta >= 0) {
            return;
        }
        $status = $product->stockStatus();
        if ($status === 'in_stock') {
            return;
        }
        try {
            $notification = new LowStockNotification($product);

            if ($product->vendor_id && $product->vendor && $product->vendor->user) {
                $product->vendor->user->notify($notification);
            }
            User::where('is_admin', true)->get()->each->notify($notification);
        } catch (\Throwable) {
            // Non-critical — never let a notification failure break a stock change.
        }
    }
}
