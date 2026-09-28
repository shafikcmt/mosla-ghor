<?php

namespace App\Services;

use App\Jobs\SendMetaConversion;
use App\Models\Combo;
use App\Models\MetaConversionEvent;
use App\Models\Order;
use App\Support\MetaPixel;
use App\Support\MetaUserData;
use Illuminate\Http\Request;
use Illuminate\Database\DeadlockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecordMetaPurchase
{
    /** Called exclusively inside OrderController::store's website order transaction. */
    public function record(Order $order, Request $request): void
    {
        $transactionLevel = DB::transactionLevel();
        try {
            if (! app(MetaConversionsApi::class)->configured() || ! MetaPixel::active()
                || ! $request->routeIs('order.store') || $request->user()?->is_admin
                || $request->user()?->role === 'vendor'
                || ($order->order_source ?? 'customer_order') !== 'customer_order'
                || $order->created_by_vendor_id || $order->enquiry_id
                || ! in_array($order->order_type, ['single_product', 'custom', 'fixed_combo'], true)) {
                return;
            }
            $order->loadMissing('items');
            if ($order->items->contains(fn ($item) => $item->sell_type !== 'retail')) {
                return;
            }
            if ($order->combo_id && Combo::whereKey($order->combo_id)->value('sell_type') !== 'retail') {
                return;
            }
            $own = MetaPixel::platformScopeOwn();
            // Legacy fixed-combo lines lose vendor ownership. Fail closed for own scope;
            // do not rewrite combo/vendor-order fulfillment as part of analytics.
            if ($own && ($order->combo_id || $order->order_type === 'fixed_combo')) {
                return;
            }
            $params = MetaPixel::platformPurchaseParams($order, $own);
            if (! $params || ! ($pixels = MetaPixel::platformIds())) {
                return;
            }
            $time = $order->created_at->timestamp;
            $snapshot = [
                'scope' => $own ? 'own' : 'all',
                'event' => [
                    'event_name' => 'Purchase',
                    'event_id' => (string) $order->order_number,
                    'event_time' => $time,
                    'action_source' => 'website',
                    // Actual checkout submission URL, without query strings or private tokens.
                    'event_source_url' => rtrim(config('app.url'), '/').'/order',
                    'user_data' => MetaUserData::forPurchase($order, $request),
                    'custom_data' => $params,
                ],
                'test_event_code' => MetaPixel::settings()?->test_event_code,
            ];
            // Savepoint keeps optional analytics errors from poisoning the order transaction.
            $ids = DB::transaction(function () use ($order, $pixels, $time, $snapshot) {
                $ids = [];
                foreach ($pixels as $pixel) {
                    $event = MetaConversionEvent::firstOrCreate([
                        'pixel_id' => $pixel, 'event_name' => 'Purchase', 'event_id' => $order->order_number,
                    ], [
                        'event_time' => $time, 'order_id' => $order->id, 'payload' => $snapshot,
                        'status' => 'pending', 'next_attempt_at' => now(),
                    ]);
                    if ($event->wasRecentlyCreated) {
                        $ids[] = $event->id;
                    }
                }

                return $ids;
            });
            DB::afterCommit(function () use ($ids) {
                foreach ($ids as $id) {
                    self::dispatch($id);
                }
            });
        } catch (\Throwable $exception) {
            // MySQL deadlocks may abort the WHOLE transaction, not just our savepoint.
            // Never continue checkout as if its order/stock writes were still intact.
            $transactionLost = $exception instanceof DeadlockException;
            if ($transactionLevel > 0) {
                try {
                    $transactionLost = $transactionLost || DB::transactionLevel() !== $transactionLevel
                        || ! DB::connection()->getPdo()->inTransaction();
                } catch (\Throwable) {
                    $transactionLost = true;
                }
            }
            if ($transactionLost) {
                throw new \RuntimeException('Order transaction interrupted; please retry.');
            }
            self::warn('Meta Purchase recording unavailable.');
        }
    }

    /** Dispatch failures leave the committed event recoverable, without failing checkout. */
    public static function dispatch(int $id): bool
    {
        $reservation = now()->addMinutes(10);
        try {
            if (in_array(config('queue.connections.'.config('meta.queue_connection').'.driver'), ['sync', 'null', 'deferred', 'background', 'failover'], true)) {
                self::warn('Meta CAPI requires a persistent asynchronous queue.');
                return false;
            }
            // Reserve dispatch separately from delivery so a stopped worker does not
            // cause every scheduler run to enqueue the same first 100 records.
            if (! MetaConversionEvent::whereKey($id)->readyToDispatch()->update(['queued_until' => $reservation])) {
                return false;
            }
            SendMetaConversion::dispatch($id)->afterCommit();

            return true;
        } catch (\Throwable) {
            try {
                MetaConversionEvent::whereKey($id)->where('queued_until', $reservation)
                    ->update(['queued_until' => null]);
            } catch (\Throwable) {
                // Reservation expires even when the database is temporarily unavailable.
            }
            self::warn('Meta CAPI dispatch unavailable.', ['event_record_id' => $id]);
            return false;
        }
    }

    private static function warn(string $message, array $context = []): void
    {
        try {
            Log::warning($message, $context);
        } catch (\Throwable) {
            // Analytics logging must not turn a recoverable failure into a failed order.
        }
    }
}
