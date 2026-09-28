<?php

namespace App\Jobs;

use App\Models\MarketingSetting;
use App\Models\MetaConversionEvent;
use App\Services\MetaConversionsApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendMetaConversion implements ShouldQueue
{
    use Queueable;

    public const MAX_ATTEMPTS = 5;
    public const MAX_AGE_SECONDS = 86400;

    public int $tries = 5;
    public int $timeout = 30;

    public function __construct(public int $eventRecordId)
    {
        $this->onConnection(config('meta.queue_connection'));
        $this->onQueue(config('meta.queue'));
        $this->afterCommit();
    }

    public function handle(MetaConversionsApi $api): void
    {
        if (! $api->configured()) {
            return; // Pending rows can resume after credentials/activation are restored.
        }
        $claim = (string) Str::uuid();
        $claimed = MetaConversionEvent::whereKey($this->eventRecordId)->due()->update([
            'status' => 'sending', 'claim_token' => $claim, 'locked_until' => now()->addSeconds(60),
        ]);
        if (! $claimed) {
            return;
        }
        try {
            $event = MetaConversionEvent::findOrFail($this->eventRecordId);
            if ($event->attempts >= self::MAX_ATTEMPTS || $event->event_time < now()->timestamp - self::MAX_AGE_SECONDS) {
                $this->finish($claim, 'failed', 'delivery_limit');
                return;
            }
            $settings = MarketingSetting::query()->first();
            $payload = $event->payload;
            if (! $settings?->pixel_enabled || ! in_array($event->pixel_id, (array) $settings->pixel_ids, true)
                || $settings->platform_pixel_scope !== $payload['scope']) {
                $this->finish($claim, 'cancelled', 'destination_or_scope_changed');
                return;
            }
            MetaConversionEvent::whereKey($event->id)->where('claim_token', $claim)
                ->update(['attempts' => DB::raw('attempts + 1')]);
            $attempt = $event->attempts + 1;
            $result = $api->send($event);
            $status = $result['status'];
            if ($status === 'paused') {
                $status = 'retry';
            }
            if ($status === 'retry' && $attempt >= self::MAX_ATTEMPTS) {
                $status = 'failed';
            }
            $delay = [60, 300, 900, 3600][$attempt - 1] ?? 3600;
            $this->finish($claim, $status, $result['error'], $status === 'retry' ? $delay : null);
            if ($status === 'retry') {
                $this->release($delay);
            }
        } catch (\Throwable) {
            // Never let HTTP request exceptions or decrypted context reach failed_jobs.
            $this->finish($claim, 'failed', 'delivery_internal_error');
        }
    }

    private function finish(string $claim, string $status, ?string $error, ?int $delay = null): void
    {
        MetaConversionEvent::whereKey($this->eventRecordId)->where('claim_token', $claim)->update([
            'status' => $status, 'last_error' => $error, 'claim_token' => null, 'locked_until' => null,
            'next_attempt_at' => $delay ? now()->addSeconds($delay) : null,
            'queued_until' => $delay ? now()->addSeconds($delay)->addMinutes(10) : null,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        // Do not erase another worker's lease. Recovery handles expired claims.
        MetaConversionEvent::whereKey($this->eventRecordId)->whereIn('status', ['pending', 'retry'])
            ->update(['last_error' => 'queue_failed']);
    }
}
