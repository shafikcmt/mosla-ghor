<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('meta:recover', function () {
    if (! app(\App\Services\MetaConversionsApi::class)->configured()) {
        $this->info('Meta CAPI is disabled or unconfigured.');
        return;
    }
    $count = 0;
    // Stop expired records even when no worker is available to consume queue messages.
    \App\Models\MetaConversionEvent::query()->due()
        ->where('event_time', '<', now()->timestamp - \App\Jobs\SendMetaConversion::MAX_AGE_SECONDS)
        ->update(['status' => 'failed', 'last_error' => 'delivery_limit', 'queued_until' => null,
            'claim_token' => null, 'locked_until' => null]);
    \App\Models\MetaConversionEvent::query()->readyToDispatch()->orderBy('id')->limit(100)->pluck('id')
        ->each(function ($id) use (&$count) {
            $count += (int) \App\Services\RecordMetaPurchase::dispatch($id);
        });
    $this->info("Dispatched {$count} pending Meta events.");
})->purpose('Recover committed Meta events after dispatch failures or expired worker claims');

\Illuminate\Support\Facades\Schedule::command('meta:recover')->everyFiveMinutes()->withoutOverlapping();
